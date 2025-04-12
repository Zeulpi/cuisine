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

    // #[Route('/api/user/update', name: 'api_user_update', methods: ['POST'])]
    public function userUpdate(Request $request) : JsonResponse
    {
        try {
            $alerts = [];
            // Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);

            $receivedData = [
                "sentToken" => $request->request->get('token'),
                "sentEmail" => $request->request->get('userEmail'),
                "sentPassword" => $request->request->get('validatedPassword'),
                "sentUserName" => $request->request->get('userName'),
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

            // Si un nouveau mot de passe est envoyé, on le hash et on le met à jour
            if (!empty($receivedData["sentPassword"])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $receivedData["sentPassword"]);
                $user->setPassword($hashedPassword);
            }

            // Verification du mail recu
            if ($receivedData['sentEmail'] !== $user->getEmail()) { // le sentEmail est différent de celui d'origine
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $receivedData['sentEmail']]); // on cherche si un autre utilisateur utilise deja le mail demandé
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
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

            // ✅ Traitement de l’image (ajouté sans retour anticipé)
            $uploadedFile = $request->files->get('userImage');
            if ($uploadedFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/avatar';
                $filename = uniqid('avatar_') . '.' . $uploadedFile->guessExtension();
                $previousImage = $user->getUserImg();
                try {
                    // Déplacement du nouveau fichier
                    $uploadedFile->move($uploadDir, $filename);
                    $user->setUserImg($filename);

                    // 🧼 Suppression de l'ancien avatar (s'il y en a un)
                    if (!empty($previousImage)) {
                        $oldFile = $uploadDir . '/' . $previousImage;
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                } catch (\Exception $e) {
                    $alerts['userImage'] = "Erreur lors de l’envoi de l’image.";
                }
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
