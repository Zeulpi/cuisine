<?php

namespace App\Controller\API\User;

use App\Entity\Fridge;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserCreateController extends AbstractController
{
    private $passwordHasher;
    private $entityManager;
    private $jwtManager;
    private $serializer;
    private $jwtEncoder;

    // Injection des services dans le constructeur
    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager, SerializerInterface $serializer, JWTEncoderInterface $jwtEncoder)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
        $this->serializer = $serializer;
        $this->jwtEncoder = $jwtEncoder;
    }

    // #[Route('/api/user/register', name: 'api_user_register', methods: ['POST'])]
    public function userCreate(Request $request) : JsonResponse
    {
        try {
            $alerts = [];

            // Verification du CAPTCHA
            $captchaToken = $request->request->get('captchaToken');
            $captchaSecret = $this->getParameter('captcha_secret');
            $verifyResponse = file_get_contents(
                'https://www.google.com/recaptcha/api/siteverify?secret=' . $captchaSecret . '&response=' . $captchaToken
            );
            $responseData = json_decode($verifyResponse);
            if (!$responseData->success) {
                return new JsonResponse([
                    'error' => 'Échec de la vérification du captcha. Veuillez réessayer.',
                ], 400);
            }

            
            // Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);

            $receivedData = [
                "sentEmail" => $request->request->get('userEmail'),
                "sentPassword" => $request->request->get('validatedPassword'),
                "sentUserName" => $request->request->get('userName'),
            ];

            $user = new User();

            // Si un nouveau mot de passe est envoyé, on le hash et on le met à jour
            if (!empty($receivedData["sentPassword"])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $receivedData["sentPassword"]);
                $user->setPassword($hashedPassword);
            }

            // Verification du mail recu
            if ($receivedData['sentEmail']) {
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $receivedData['sentEmail']]); // on cherche si un autre utilisateur utilise deja le mail demandé
                if ($existingUser) {
                    // L'email est déjà utilisé on prépare un message d'erreur
                    $alerts['email'] = "Cette adresse email est déjà utilisée par un autre utilisateur.";
                } else {
                    // L'email est libre, on peut le mettre a jour
                    $user->setEmail($receivedData["sentEmail"]);
                }
            }
            
            // Vérification du username recu
            if (!empty($receivedData["sentUserName"])) {
                  $user->setUserName($receivedData["sentUserName"]);
              }

            // Traitement de l’image (ajouté sans retour anticipé)
            $uploadedFile = $request->files->get('userImage');
            if ($uploadedFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/avatar';
                $filename = uniqid('avatar_') . '.' . $uploadedFile->guessExtension();
                try {
                    // Déplacement du nouveau fichier
                    $uploadedFile->move($uploadDir, $filename);
                    $user->setUserImg($filename);
                } catch (\Exception $e) {
                    $alerts['userImage'] = "Erreur lors de l’envoi de l’image.";
                }
            }

            if (!empty($alerts['email'])) {
                return new JsonResponse([
                    'alerts' => $alerts,
                    'success' => false
                ]);
            }

            // $this->entityManager->persist($user);
            // $this->entityManager->flush();


            // Création d'un fridge pour le nouvel utilisateur
            $userFridge = new Fridge();
            $userFridge->setUser($user);
            $user->setFridge($userFridge);

            // Persist et flush pour sauvegarder le fridge en bdd
            $this->entityManager->persist($userFridge);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Crée un nouveau token pour mettre a jour le front
            $newToken = $this->jwtManager->create($user);

            return new JsonResponse([
                'message' => 'Utilisateur Crée avec succes',
                'email' => $receivedData["sentEmail"],
                'alerts' => $alerts,
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
