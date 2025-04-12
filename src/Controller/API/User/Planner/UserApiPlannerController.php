<?php

namespace App\Controller\API\User\Planner;

use App\Entity\User;
use App\Model\Planner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserApiPlannerController extends AbstractController
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

    // #[Route('/api/user/planner/addrecipe', name: 'api_user_planner_add', methods: ['POST'])]
    public function addRecipeToPlanner(Request $request, EntityManagerInterface $entityManager) : JsonResponse
    {
        try {
            $alerts = [];
            
            // Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);

            $receivedData = [
                "sentToken" => $data('token'),
                "sentDay" => $data('day'),
                "sentRecipeId" => $data('recipeId'),
                "sentPortions" => $data('portions'),
            ];

            if (!$receivedData['sentToken'] || !$receivedData['sentDay'] || !$receivedData['sentRecipeId'] || !$receivedData['sentPortions']) {
                return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
            }

            if (!is_int($receivedData['sentRecipeId']) || $receivedData['sentRecipeId'] <= 0) {
                return new JsonResponse(['error' => 'ID de recette invalide'], 400);
            }

            if (!is_int($receivedData['sentPortions']) || $receivedData['sentPortions'] <= 0) {
                return new JsonResponse(['error' => 'Le nombre de portions doit être supérieur à zéro'], 400);
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

             // Vérifier si l'utilisateur a un planner actif
            if (empty($user->getUserPlanners())) {
                $user->setUserPlanners($user->initializePlanners());  // Initialiser si nécessaire
            }

            // Recuperer le planner actif
            $planner = $user->getUserPlanners()[0];

            $planner->addRecipe($receivedData['sentDay'], $receivedData['sentRecipeId'], $receivedData['sentPortions']);

            // Sauvegarder les changements
            $entityManager->persist($user);
            $entityManager->flush();

            // Retourner une réponse de succès
            return new JsonResponse(['success' => true, 'message' => 'Recette ajoutée avec succès']);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
