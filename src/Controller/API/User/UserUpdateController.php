<?php

namespace App\Controller\API\User;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;  // Utilisation de la version moderne
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;  // Injection du EntityManager
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;  // Importation du service JWT
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserUpdateController extends AbstractController
{
    private $passwordHasher;
    private $entityManager;  // Déclaration de l'EntityManager
    private $jwtManager;  // Déclaration du service JWT
    private $serializer;
    private $jwtEncoder;

    // Injection des services dans le constructeur
    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager, SerializerInterface $serializer, JWTEncoderInterface $jwtEncoder)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;  // Attribution de l'EntityManager à la propriété
        $this->jwtManager = $jwtManager;  // Attribution du service JWT à la propriété
        $this->serializer = $serializer;
        $this->jwtEncoder = $jwtEncoder;
    }

    #[Route('/api/user/update', name: 'api_user_update', methods: ['POST', 'GET'])]
    public function userUpdate(Request $request)
    {
        try {
            $alerts = [];
            // Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);

            $receivedData = [
                "sentToken" => $data['token'] ?? "",
                "sentEmail" => $data['userEmail'] ?? "",
                "sentPassword" => $data['validatedPassword'] ?? "",
                "sentUserName" => $data['userName'] ?? "",
            ];

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

            // Verifier le token pour s'assurer que c'est bien le user loggé qui modifie son compte
            if (!$payload || $payload['email'] !== $user->getUserIdentifier()) {
                return new JsonResponse(['error' => 'Token invalide ou utilisateur non autorisé'], 401);
            }

            $password = $user->getPassword();

            // Si un nouveau mot de passe est envoyé, on le hash et on le met à jour
            if (!empty($receivedData["sentPassword"])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $receivedData["sentPassword"]);
                $user->setPassword($hashedPassword);
            }

            // Verification du mail recu
            if ($receivedData['sentEmail'] !== $user->getEmail()) { // le sentEmail est différent de celui d'origine
                $existingUser = $this->entityManager->getRepository(User::class)->findOneByEmail($receivedData['sentEmail']); // on cherche si un autre utilisateur utilise deja le mail demandé
                if ($existingUser) {
                    // L'email est déjà utilisé on prépare un message d'erreur
                    $alerts['email'] = "Cette adresse email est déjà utilisée par un autre utilisateur.";
                } else {
                    // L'email est libre, on peut le mettre a jour
                    $user->setEmail($receivedData["sentEmail"]);
                }
            }
            
            // Vérification du username recu
            if (
                !empty($receivedData["sentUserName"]) &&
                $receivedData["sentUserName"] !== $user->getUserName()
              ) {
                  $user->setUserName($receivedData["sentUserName"]);
              }

              $this->entityManager->persist($user);
              $this->entityManager->flush();

            // Crée un nouveau token pour mettre a jour le front
            $newToken = $this->jwtManager->create($user);

            return new JsonResponse([
                'message' => 'Utilisateur mis à jour avec succès',
                'token' => $newToken,
                'alerts' => $alerts,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
