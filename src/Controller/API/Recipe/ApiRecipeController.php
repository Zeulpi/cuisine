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

final class ApiRecipeController extends AbstractController{
    // #[Route('/api/recipes', name: 'api_recipe_list', methods: ['GET'])]
    public function listRecipes(
        Request $request,
        RecipeRepository $recipeRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $sort = $request->query->get('sort') ?? '';
        $searchTerm = $request->query->get('search') ?? '';
        $tagFilter = $request->query->get('tags') ?? '';
        $tagList = $tagFilter ? explode(',', $tagFilter) : [];
        $ingFilter = $request->query->get('ingredients') ?? '{}';
        $decodedIngredients = json_decode($ingFilter, true);


        $queryBuilder = $recipeRepository->createQueryBuilder('r')
            ->leftJoin('r.recipeTags', 't')      // pour récupérer les tags à afficher
            ->addSelect('t');

        switch ($sort) {
            case 'name_asc':
                $queryBuilder->orderBy('r.recipeName', 'ASC');
                break;
            case 'name_desc':
                $queryBuilder->orderBy('r.recipeName', 'DESC');
                break;
            case 'duration_asc':
                // à implémenter plus tard
                break;
            case null:
            default:
                $queryBuilder->orderBy('r.recipeName', 'ASC');
                break;
        }
            
         
        // 🔍 Filtrage par tags
        $applyGroupBy = false;
        // Validation des paramètres
        if (!is_array($tagList) || array_filter($tagList, fn($tag) => !is_string($tag))) {
            return new JsonResponse(['error' => 'Paramètres de tags invalides.'], 400);
        }
        if (!empty($tagList)) {
            $subTags = $recipeRepository->createQueryBuilder('r2')
                ->select('r2.id')
                ->join('r2.recipeTags', 't2')
                ->where('t2.tagName IN (:tags)')
                ->groupBy('r2.id')
                ->having('COUNT(DISTINCT t2.id) = :nbTags')
                ->getDQL(); // on récupère la DQL pour la sous-requête
        
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in('r.id', $subTags))
                ->setParameter('tags', $tagList)
                ->setParameter('nbTags', count($tagList));
        }


        // 🔍 Filtrage par nom (search)
        if (!is_string($searchTerm)) {
            return new JsonResponse(['error' => 'Paramètre de recherche invalide.'], 400);
        }
        if (!empty($searchTerm)) {
            $queryBuilder
                ->andWhere('r.recipeName LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');

            if (!$applyGroupBy && !empty($tagList)) {
                $queryBuilder->groupBy('r.id');
            }
        }


        // 🔍 Filtrage par ingrédients
        if (!empty($decodedIngredients)) {
            $userIngredientIds = array_map('intval', array_keys($decodedIngredients));

            $subIngs = $recipeRepository->createQueryBuilder('recipeIngFilter')
                ->select('recipeIngFilter.id')
                ->join('recipeIngFilter.recipeIngredient', 'requiredIng')
                ->where('requiredIng.id IN (:userIngredientIds)')
                ->groupBy('recipeIngFilter.id')
                ->having('COUNT(DISTINCT requiredIng.id) = (
                    SELECT COUNT(DISTINCT totalIng.id)
                    FROM App\Entity\Recipe recipeTotal
                    JOIN recipeTotal.recipeIngredient totalIng
                    WHERE recipeTotal.id = recipeIngFilter.id
                )')
                ->getDQL();
        
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in('r.id', $subIngs))
                ->setParameter('userIngredientIds', $userIngredientIds);
        }

        try {
            $pagination = $paginator->paginate(
                $queryBuilder,
                $page,
                $limit,
                ['useOutputWalkers' => true]
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }

        $recipes = [];
        foreach ($pagination->getItems() as $recipe) {
            $recipes[$recipe->getId()] = [
                'id' => $recipe->getId(),
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
                'portions' => $recipe->getRecipePortions(),
            ];
        }

        return new JsonResponse([
            'recipes' => $recipes,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($pagination->getTotalItemCount() / $limit),
                'totalItems' => $pagination->getTotalItemCount(),
            ],
            'debug_tagList' => $tagList,
        ]);
    }

    // #[Route('/api/tags', name: 'api_tag_list', methods: ['GET'])]
    public function getTags(TagRepository $tagRepository): JsonResponse
    {
        $tags = $tagRepository->findAll();

        $data = array_map(fn($tag) => [
            'name' => $tag->getTagName(),
            'color' => $tag->getTagColor(),
        ], $tags);

        return new JsonResponse($data);
    }
}
