<?php

namespace App\Controller\API\User\Planner;

use App\Entity\User;
use App\Model\Planner;
use App\Entity\Recipe;
use App\Entity\Ingredient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Model\PlannerRecipes;

class UserApiShoppingController extends AbstractController
{
    private $entityManager;
    private $jwtManager;
    private $serializer;
    private $jwtEncoder;

    // Injection des services dans le constructeur
    public function __construct(EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager, SerializerInterface $serializer, JWTEncoderInterface $jwtEncoder)
    {
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
        $this->serializer = $serializer;
        $this->jwtEncoder = $jwtEncoder;
    }

    public function getShopping(Request $request, EntityManagerInterface $entityManager) : JsonResponse
    {
        try {
            // Récupérer les données envoyées
            $data = json_decode($request->getContent(), true);
            $sentToken = $data['token'] ?? null;
            $sentRecipes = $data['recipes'] ?? null;

            if (!$sentToken || !$sentRecipes) {
                return new JsonResponse(['error' => 'Token or recipes missing'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Requête pour récupérer les recettes demandées dans sentRecipes
            $queryBuilder = $entityManager->createQueryBuilder();

            $queryBuilder->select('r')
                ->from(Recipe::class, 'r')
                ->where($queryBuilder->expr()->in('r.id', array_keys($sentRecipes))); // Filtrer les recettes par ID

            $recipes = $queryBuilder->getQuery()->getResult(); // Exécuter la requête et récupérer les résultats

            // Initialiser un tableau pour stocker les ingrédients
            $ingredientsData = [];

            // Parcourir les recettes récupérées
            foreach ($recipes as $recipe) {
                // Récupérer les informations de portions et de quantités
                $recipePortions = $recipe->getRecipePortions(); // Nombre de portions pour la recette
                $recipeQuantities = $recipe->getRecipeQuantities(); // Quantités des ingrédients pour la recette (champ JSON)

                // Pour chaque ingrédient dans recipeQuantities, calculer la quantité ajustée
                foreach ($recipeQuantities as $ingredientId => $data) {
                    $ingredient = $entityManager->getRepository(Ingredient::class)->find($ingredientId); // Récupérer l'ingrédient par son ID
                    $ingredientName = $ingredient ? $ingredient->getIngredientName() : 'Unknown'; // Nom de l'ingrédient, avec un fallback

                    // Quantité initiale et unité
                    $ingredientQuantity = $data['quantity'];
                    $ingredientUnit = $data['unit'] ?? 'unit'; // Valeur par défaut pour l'unité

                    // Ajuster la quantité en fonction des portions demandées
                    $requestedPortions = $sentRecipes[$recipe->getId()];
                    $adjustedQuantity = ($ingredientQuantity * $requestedPortions) / $recipePortions;

                    // Ajouter ou mettre à jour la quantité pour l'unité correspondante
                    if (isset($ingredientsData[$ingredientId])) {
                        // Vérifier si l'unité existe déjà pour cet ingrédient
                        $foundUnit = false;
                        foreach ($ingredientsData[$ingredientId] as &$quantObj) {
                            if ($quantObj['unit'] === $ingredientUnit) {
                                // Si l'unité existe déjà, on ajoute la quantité
                                $quantObj['quantity'] += $adjustedQuantity;
                                $foundUnit = true;
                                break;
                            }
                        }

                        // Si l'unité n'existe pas encore, on l'ajoute
                        if (!$foundUnit) {
                            $ingredientsData[$ingredientId][] = [
                                'name' => $ingredient->getIngredientName(),
                                'quantity' => $adjustedQuantity,
                                'unit' => $ingredientUnit
                            ];
                        }
                    } else {
                        // Si l'ingrédient n'existe pas encore, on le crée
                        $ingredientsData[$ingredientId][] = [
                            'name' => $ingredient->getIngredientName(),
                            'quantity' => $adjustedQuantity,
                            'unit' => $ingredientUnit
                        ];
                    }
                }
            }

            // Retourner les résultats sous forme de JSON
            return new JsonResponse(['message' => 'Liste récupérée', 'ingredients' => $ingredientsData]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
