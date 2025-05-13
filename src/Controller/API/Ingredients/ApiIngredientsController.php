<?php

namespace App\Controller\API\Ingredients;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;

final class ApiIngredientsController extends AbstractController{
    // #[Route('/api/ingredients', name: 'api_ingredients_list', methods: ['GET'])]
    public function listIngredients(
        Request $request,
        IngredientRepository $ingredientRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $sort = $request->query->get('sort') ?? '';
        $searchTerm = $request->query->get('search') ?? '';

        $queryBuilder = $ingredientRepository->createQueryBuilder('i');

        switch ($sort) {
            case 'name_asc':
                $queryBuilder->orderBy('i.ingredientName', 'ASC');
                break;
            case 'name_desc':
                $queryBuilder->orderBy('i.ingredientName', 'DESC');
                break;
            case null:
            default:
                $queryBuilder->orderBy('i.ingredientName', 'ASC');
                break;
        }

        // 🔍 Filtrage par nom (search)
        if (!is_string($searchTerm)) {
            return new JsonResponse(['error' => 'Paramètre de recherche invalide.'], 400);
        }
        if (!empty($searchTerm)) {
            $queryBuilder
                ->andWhere('i.ingredientName LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
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

        $ingredients = [];
        foreach ($pagination->getItems() as $ingredient) {
            $ingredients[$ingredient->getId()] = [
                'id' => $ingredient->getId(),
                'name' => $ingredient->getIngredientName(),
                'image' => $ingredient->getIngredientImg(),
                'units' => $ingredient->getIngredientUnit(),
            ];
        }

        return new JsonResponse([
            'ingredients' => $ingredients,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($pagination->getTotalItemCount() / $limit),
                'totalItems' => $pagination->getTotalItemCount(),
            ],
        ]);
    }
}
