<?php

namespace App\Controller\Recipe;

use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use App\Form\RecipeType;
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
    public function getRecipe(RecipeRepository $repository): Response
    {
        // Récupérer le nom du contrôleur
        $controllerName = self::class;

        // Récupérer les objets depuis la base de données
        $recipes = $repository->findAll();

        // Transformer les entités en tableau
        $recipesArray = array_map(function ($entity) {
            return [
                'id' => $entity->getId(),
                'name' => $entity->getRecipeName(),
                'img' => $entity->getRecipeImg(),
            ];
        }, $recipes);


        return $this->render('recipe/read.html.twig', [
            'controller_name' => $controllerName,
            'recipesList' => $recipesArray,
        ]);
    }
    
}
