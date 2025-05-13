<?php

namespace App\Controller\API\User\Fridge;

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

class UserApiFridgeController extends AbstractController
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

    // #[Route('/api/user/fridge/addingredient', name: 'api_user_fridge_add', methods: ['POST'])]
    public function addIngredientToFridge(Request $request, EntityManagerInterface $entityManager) : JsonResponse
    {
        try {
            // Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);

            $receivedData = [
                "sentToken" => $data['token'],
                "sentIngredientId" => $data['ingredientId'],
                "sentQuantity" => $data['ingredientQuantity'],
                "sentUnit" => $data['ingredientUnit'],
            ];

            if (!$receivedData['sentToken'] || !$receivedData['sentIngredientId'] || !$receivedData['sentQuantity']) {
                return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
            }

            if (!is_int($receivedData['sentIngredientId']) || $receivedData['sentIngredientId'] <= 0) {
                return new JsonResponse(['error' => 'ID d\'ingredient invalide'], 400);
            }

            if (!is_numeric($receivedData['sentQuantity'])) {
                return new JsonResponse(['error' => 'Quantité d\'ingrédient invalide'], 400);
            }

            try {
                $payload = $this->jwtEncoder->decode($receivedData["sentToken"]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Token invalide',
                    'details' => $e->getMessage(),
                ], 401);
            }
            // Trouver l'utilisateur par son email
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);

            // Verifier si le User existe
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }
            // Verifier le token pour s'assurer que le bon user accede au bon planner
            if (!$payload || $payload['email'] !== $user->getUserIdentifier()) {
                return new JsonResponse(['error' => 'Token invalide ou utilisateur non autorisé'], 401);
            }
            
            // ajouter l'ingrédient recu au fridge User (avec qty et unit)
            $userFridge = $user->getFridge();
            $userFridge->addIngredientToInventory($receivedData['sentIngredientId'], $receivedData['sentQuantity'], $receivedData['sentUnit'], $entityManager);

            // Sauvegarder les changements
            $entityManager->persist($user);
            $entityManager->flush();

            $userInventory = $userFridge->getInventory();
            
            // Raffraichir $user et générer un nouveau token
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);
            $newToken = $this->jwtManager->create($user);

            // Retourner le token JWT dans la réponse
            return new JsonResponse(['message' => 'Ingredient ajouté au fridge', 'token' => $newToken, 'inventory' => $userInventory, 'updated' => 'updated']);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    // #[Route('/api/user/fridge/get', name: 'api_user_fridge_get', methods: ['GET'])]
    public function getIngredientsFromFridge(Request $request, EntityManagerInterface $entityManager) : JsonResponse
    {
        try {// Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);
            $sentToken = $request->query->get('token');

            if (!$sentToken) {
                return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
            }

            try {
                $payload = $this->jwtEncoder->decode($sentToken);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Token invalide',
                    'details' => $e->getMessage(),
                ], 401);
            }
            // Trouver l'utilisateur par son email
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);

            // Verifier si le User existe
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }
            // Verifier le token pour s'assurer que le bon user accede au bon planner
            if (!$payload || $payload['email'] !== $user->getUserIdentifier()) {
                return new JsonResponse(['error' => 'Token invalide ou utilisateur non autorisé'], 401);
            }


            // Acceder au Fridge de l'utilisateur et récupérer l'inventaire
            $userFridge = $user->getFridge();
            $userInventory = $userFridge->getInventory();

            // Générer un nouveau token
            $newToken = $this->jwtManager->create($user);


            // Retourner le token JWT dans la réponse + la liste des ingrédients du fridge
            return new JsonResponse(['message' => 'Recupération de l\'inventaire', 'token' => $newToken, 'inventory' => $userInventory]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
