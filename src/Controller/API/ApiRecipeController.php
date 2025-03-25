<?php

namespace App\Controller\API;

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

final class ApiRecipeController extends AbstractController{
    #[Route('/api/recipes', name: 'api_recipe_list', methods: ['GET'])]
    public function listRecipes(
        Request $request,
        RecipeRepository $recipeRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $searchTerm = $request->query->get('search');
        $tagFilter = $request->query->get('tags');
        $tagList = $tagFilter ? explode(',', $tagFilter) : [];

        $queryBuilder = $recipeRepository->createQueryBuilder('r')
            ->leftJoin('r.recipeTags', 't')->addSelect('t')
            ->orderBy('r.id', 'DESC');

        $applyGroupBy = false;

        if (!empty($tagList)) {
            $applyGroupBy = true;

            $queryBuilder
                ->andWhere('t.name IN (:tags)')
                ->setParameter('tags', $tagList)
                ->groupBy('r.id')
                ->having('COUNT(DISTINCT t.id) = :nbTags')
                ->setParameter('nbTags', count($tagList));
        }

        if (!empty($searchTerm)) {
            $queryBuilder
                ->andWhere('r.recipeName LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');

            // ⚠️ ajouter groupBy si la recherche + tag combinés
            if (! $applyGroupBy && !empty($tagList)) {
                $queryBuilder->groupBy('r.id');
            }
        }


        // 🔍 Filtrage par nom (search)
        if (!empty($searchTerm)) {
            $queryBuilder
                ->andWhere('r.recipeName LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        // 🏷️ Filtrage par tags (tous les tags doivent être présents)
        if (!empty($tagList)) {
            $queryBuilder
                ->andWhere('t.name IN (:tags)')
                ->setParameter('tags', $tagList)
                ->having('COUNT(DISTINCT t.id) = :nbTags')
                ->setParameter('nbTags', count($tagList));
        }

        // 🔄 (Prévu pour plus tard) Tri dynamique :
        // Exemple futur ?sort=duration / ?sort=rating
        // $sort = $request->query->get('sort');
        // if ($sort === 'duration') {
        //     $queryBuilder->orderBy('r.recipeDuration', 'ASC');
        // }

        $pagination = $paginator->paginate($queryBuilder, $page, $limit);

        $recipes = [];
        foreach ($pagination->getItems() as $recipe) {
        $recipes[$recipe->getId()] = [
            'name' => $recipe->getRecipeName(),
            'image' => $recipe->getRecipeImg(),
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
            'tags' => array_map(fn($tag) => [
                'name' => $tag->getTagName(),
                'color' => $tag->getTagColor()
            ], $recipe->getRecipeTags()->toArray()),
        ];
    }

        return new JsonResponse([
            'recipes' => $recipes,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($pagination->getTotalItemCount() / $limit),
                'totalItems' => $pagination->getTotalItemCount(),
            ]
        ]);
    }
    
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

        $ingredients = [];
        foreach ($recipe->getRecipeIngredient() as $ingredient) {
            $ingredients[$ingredient->getId()] = [
                'name' => $ingredient->getIngredientName(),
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

        // recupérer les quantités des ingrédients
        $recipeQuantities = $recipe->getRecipeQuantities(); // Récupérer le JSON
        if (!is_array($recipeQuantities)) {
            $recipeQuantities = json_decode($recipeQuantities, true) ?? []; // Décoder si nécessaire
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
                ], $step->getStepOperations()->toArray())
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
            'quantities' => $recipeQuantities,
            'portions' => $recipe->getRecipePortions(),
            'ingredients' => $ingredients,
            'steps' => $steps,
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
}
