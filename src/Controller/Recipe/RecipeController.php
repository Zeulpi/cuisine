<?php

namespace App\Controller\Recipe;

use App\Repository\RecipeRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;


final class RecipeController extends AbstractController{
    private $doctrine;

    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/recipe', name: 'app_recipe')]
    public function getRecipe(RecipeRepository $repository, TagRepository $tagRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous devez être connecté pour accedert a cette page.');
            return $this->redirectToRoute('app_home');
        }

        // Récupérer les objets depuis la base de données
        $recipes = $repository->findBy([], ['recipeName' => 'ASC']);
        $tags = $tagRepository->findBy([], ['tagName' => 'ASC']);

        $itemsPerPage = 8;

        // Transformer les entités en tableau
        $recipesArray = array_map(function ($entity) {
            return [
                'id' => $entity->getId(),
                'name' => $entity->getRecipeName(),
                'img' => $entity->getRecipeImg(),
                'tags' => $entity->getRecipeTags()->map(function ($tag) {
                    return $tag->getId();
                })->toArray(),
            ];
        }, $recipes);


        return $this->render('recipe/read.html.twig', [
            'recipesList' => $recipesArray,
            'itemsPerPage' => $itemsPerPage,
            'tags' => $tags,
        ]);
    }
    
}
