<?php

namespace App\Controller\API\Recipe;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManager;

final class ApiRecipeDetailController extends AbstractController{
    #[Route('/api/recipes/{id}', name: 'api_recipe_detail', methods: ['GET'])]
    public function getRecipeDetail(int $id, RecipeRepository $recipeRepository): JsonResponse
    {
        // Récupérer la recette avec ses relations en une seule requête
        $recipe = $recipeRepository->createQueryBuilder('r')
            ->leftJoin('r.recipeIngredient', 'i')->addSelect('i')
            ->leftJoin('r.recipeSteps', 's')->addSelect('s')
            ->leftJoin('s.stepOperations', 'o')->addSelect('o')
            ->leftJoin('o.operation', 'op')->addSelect('op')
            ->leftJoin('o.ingredient', 'ing')->addSelect('ing')
            ->leftJoin('r.recipeTags', 't')->addSelect('t')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        // Vérifier si la recette existe
        if (!$recipe) {
            return new JsonResponse(['error' => true, 'message' => 'Recette non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        // recupérer les quantités des ingrédients
        $recipeQuantities = $recipe->getRecipeQuantities(); // Récupérer le JSON
        if (!is_array($recipeQuantities)) {
            $recipeQuantities = json_decode($recipeQuantities, true) ?? []; // Décoder si nécessaire
        }

        $ingredients = [];
        foreach ($recipe->getRecipeIngredient() as $ingredient) {
            $id = $ingredient->getId();
            $ingredients[$id] = [
                'name' => $ingredient->getIngredientName(),
                'image' => $ingredient->getIngredientImg(),
                'quantity' => $recipeQuantities[$id]['quantity'] ?? null,
                'unit' => $recipeQuantities[$id]['unit'] ?? null,
            ];
        }
        // Ajouter les ingrédients intermédiaires
        foreach ($recipe->getRecipeSteps() as $step) {
            foreach ($step->getStepOperations() as $operation) {
                $operationResult = $operation->getOperationResult();
                
                if (is_array($operationResult) && isset($operationResult['resultId']) && isset($operationResult['resultName'])) {
                    $ingredients[$operationResult['resultId']] = [
                        'name' => $operationResult['resultName']
                    ];
                }
            }
        }

        // Ordonner les steps et leur opération
        $steps = [];
        foreach ($recipe->getRecipeSteps() as $step) {
            $stepNumber = $step->getStepNumber();
            
            $steps[$stepNumber] = [
                'id' => $step->getId(),
                'description' => $step->getStepText(),
                'time' => $step->getStepTime(),
                'timeUnit' => $step->getStepTimeUnit(),
                'stepSimult' => $step->isStepSimult(),
                'operations' => array_map(fn($op) => [
                    'id' => $op->getId(),
                    'operation' => $op->getOperation()->getOperationName(),
                    'ingredient' => $op->getIngredient()
                        ? $op->getIngredient()->getId()
                        : (
                            is_array($op->getOperationResult()) && isset($op->getOperationResult()['usedIng'])
                                ? $op->getOperationResult()['usedIng']
                                : null
                        ),
                    'resultId' => is_array($op->getOperationResult()) && isset($op->getOperationResult()['resultId'])
                        ? $op->getOperationResult()['resultId']
                        : null
                ], $step->getStepOperations()->toArray()),
            ];
        }

        
        // Construire la réponse JSON
        $data = [
            'id' => $recipe->getId(),
            'name' => $recipe->getRecipeName(),
            'image' => $recipe->getRecipeImg(),
            'tags' => array_map(fn($tag) => [
                'name' => $tag->getTagName(),
                'color' => $tag->getTagColor()
            ], $recipe->getRecipeTags()->toArray()),
            'portions' => $recipe->getRecipePortions(),
            'ingredients' => $ingredients,
            'steps' => $steps,
            'duration' => (function () use ($recipe) {
                    $steps = $recipe->getRecipeSteps()->toArray();
                    $totalDuration = 0;
                    $currentBlock = [];

                    foreach ($steps as $index => $step) {
                        $time = $step->getStepTime() ?? 0;
                        $unit = strtolower($step->getStepTimeUnit() ?? 'minutes');

                        $durationInMinutes = match ($unit) {
                            'heures', 'heure' => $time * 60,
                            'secondes', 'seconde' => $time / 60,
                            default => $time
                        };

                        $currentBlock[] = $durationInMinutes;
                        $nextStep = $steps[$index + 1] ?? null;
                        $nextIsSimultaneous = $nextStep && ($nextStep->isStepSimult() ?? false);

                        if (!$nextIsSimultaneous) {
                            $totalDuration += max($currentBlock);
                            $currentBlock = [];
                        }
                    }

                    return [
                        'value' => (int) round($totalDuration),
                        'unit' => 'minutes'
                    ];
                })(),
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
}
