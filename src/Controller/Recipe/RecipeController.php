<?php

namespace App\Controller\Recipe;

use App\Entity\Recipe;
use App\Entity\Tag;
use App\Repository\RecipeRepository;
use App\Form\RecipeType;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;


final class RecipeController extends AbstractController{
    private $doctrine;

    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/recipe', name: 'app_recipe')]
    public function getRecipe(RecipeRepository $repository, TagRepository $tagRepository): Response
    {
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
