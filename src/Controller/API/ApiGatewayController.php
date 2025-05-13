<?php

// Api Gateway Conteroller
// Intercepte les requetes depuis le front et redirige vers le bon controller
// Une mauvaise route renvoie un message d'erreur

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\RecipeRepository;
use App\Repository\TagRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class ApiGatewayController extends AbstractController
{
    // Intercepte la route demandée, eventuellement avec un parametre supplémentaire (id pour detail de recette, id pour ajouter au planner, etc ...)
    #[Route('/api/{action}/{id?}', name: 'api_gateway', methods: ['GET', 'POST'])]
    public function routeRequest(
        Request $request,
        string $action,
        ?string $id = null,
        RecipeRepository $recipeRepository,
        PaginatorInterface $paginator,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager,
        )
    {
        $method = $request->getMethod();

        switch ($action) {
            case 'login': // Route pour se connecter
                if ($method === 'POST') {
                    return $this->forward('App\Controller\API\Auth\AuthController::login', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'recipes':
                if($id && $method === 'GET'){ // Detail d'une recette
                    return $this->forward('App\Controller\API\Recipe\ApiRecipeDetailController::getRecipeDetail', [
                        'id' => $id,  // Passer l'ID de la recette
                        'recipeRepository' => $recipeRepository,
                    ]);
                }
                if (!$id && $method === 'GET') {  // Liste des recettes
                    return $this->forward('App\Controller\API\Recipe\ApiRecipeController::listRecipes', [
                        'request' => $request,
                        'recipeRepository' => $recipeRepository,
                        'paginator' => $paginator,
                    ]);
                }
                break;
            case 'tags' :
                if ($method === 'GET'){
                    return $this->forward('App\Controller\API\Recipe\ApiRecipeController::getTags', [
                        'tagRepository' => $tagRepository,
                    ]); // Liste des tags existants
                }
                break;
            case 'user-refresh': // Route pour rafraîchir le token d'un user loggé
                if ($method === 'POST') {
                    return $this->forward('App\Controller\API\Auth\AuthController::refreshToken', [
                        'request' => $request,
                        'jwtEncoder' => $this->container->get(JWTEncoderInterface::class),
                        'jwtManager' => $this->container->get(JWTTokenManagerInterface::class),
                        // 'entityManager' =>$this->container->get(EntityManagerInterface::class),
                    ]);
                }
                break;
            case 'user-register': // Inscription d'un utilisateur
                if ($method === 'POST'){
                    return $this->forward('App\Controller\API\User\UserCreateController::userCreate', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'user-update': // Mise à jour des informations de l'utilisateur
                if ($method === 'POST') {
                    return $this->forward('App\Controller\API\User\UserUpdateController::userUpdate', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'user-planner-addrecipe': // Ajouter une recette au planner
                if ($method === 'POST') {
                    return $this->forward('App\Controller\API\User\Planner\UserApiPlannerController::addRecipeToPlanner', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'user-planner-get': // Ajouter une recette au planner
                if ($method === 'GET') {
                    return $this->forward('App\Controller\API\User\Planner\UserApiPlannerController::getPlanners', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'user-planner-deleterecipe': // Ajouter une recette au planner
                if ($method === 'POST') {
                    return $this->forward('App\Controller\API\User\Planner\UserApiPlannerController::removeRecipeFromPlanner', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'user-shopping-get': // Recuperer les ingrédients necessaires a un planner
                if ($method === 'POST') {
                    return $this->forward('App\Controller\API\User\Planner\UserApiPlannerController::getShopping', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'user-fridge-addtofridge': // Ajouter un ingrédient au fridge user
                if ($method === 'POST') {
                    return $this->forward('App\Controller\API\User\Fridge\UserApiFridgeController::addIngredientToFridge', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'user-fridge-getfridge': // Récupérer le contenu du fridge
                if ($method === 'GET') {
                    return $this->forward('App\Controller\API\User\Fridge\UserApiFridgeController::getIngredientsFromFridge', [
                        'request' => $request,
                    ]);
                }
                break;
            case 'user-fridge-getingredients': // Récupérer la liste paginée des ingredients du fridge
                if ($method === 'GET') {
                    return $this->forward('App\Controller\API\Ingredients\ApiIngredientsController::listIngredients', [
                        'request' => $request,
                    ]);
                }
                break;
            default:
                return new JsonResponse(['error' => 'Action inconnue'], 400);
        }
    }
}
