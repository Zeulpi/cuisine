<?php

// src/Controller/API/Auth/AuthController.php
namespace App\Controller\API\Auth;

use App\Entity\User;
use App\Service\LoginTracker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;  // Utilisation de la version moderne
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;  // Injection du EntityManager
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;  // Importation du service JWT
use Symfony\Component\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class AuthController extends AbstractController
{
    private $passwordHasher;
    private $entityManager;  // Déclaration de l'EntityManager
    private $jwtManager;  // Déclaration du service JWT
    private $serializer;
    private $loginTracker;

    // Injection des services dans le constructeur
    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager, SerializerInterface $serializer, LoginTracker $loginTracker)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;  // Attribution de l'EntityManager à la propriété
        $this->jwtManager = $jwtManager;  // Attribution du service JWT à la propriété
        $this->serializer = $serializer;
        $this->loginTracker = $loginTracker;
    }

    // #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request)
    {
        // Récupération des données JSON envoyées
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Identifiants invalides.'], 400);
        }

        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return new JsonResponse(['error' => 'Format d\'email invalide'], 400);
        }

        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email et mot de passe requis'], 400);
        }


        // Trouver l'utilisateur par son email
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);


        // Verifier les tentatives de connections et le délai de connection autorisé
        $loginAccess =  $this->loginTracker->checkAccess($user);
        if (!$loginAccess[0]) {
            return new JsonResponse(['error' => $loginAccess[1]], 400);  // Si l'accès est bloqué, renvoyer un message d'erreur
        }

        // Vérification du mot de passe avec l'encoder + verifier si user existe
        // Je mets les 2 verifs en 1 pour ne pas donner d'infos sur quel champ est invalide
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            $this->loginTracker->failedAttempt();
            return new JsonResponse(['error' => 'Identifiants invalides'], 401);
        }

        // Si tout est OK, réinitialiser les tentatives échouées
        $this->loginTracker->successAttempt();

        
        // Si l'authentification réussit, générer un token JWT
        $token = $this->jwtManager->create($user);  // Génération du token JWT

        if (empty($user->getRoles())) {
            return new JsonResponse([
                'error' => "Votre compte n'est pas encore activé. Veuillez patienter.",
            ], 403);
        }

        $serverTime = (new \DateTime())->format('d-m-Y');

        // Retourner le token JWT dans la réponse
        return new JsonResponse(['message' => 'Connexion réussie', 'token' => $token, 'serverTime' => $serverTime]);
    }

    #[Route('/api/user/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refreshToken(Request $request, JWTEncoderInterface $jwtEncoder, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return new JsonResponse(['error' => 'Token manquant'], 400);
        }

        try {
            $payload = $jwtEncoder->decode($token);

            if (!$payload || !isset($payload['email'])) {
                return new JsonResponse(['error' => 'Token invalide'], 401);
            }

            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return new JsonResponse(['error' => 'Token expiré'], 401);
            }

            $user = $entityManager->getRepository(User::class)->findOneByEmail($payload['email']);
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }

            $newToken = $jwtManager->create($user);

            if (empty($user->getRoles())) {
                return new JsonResponse([
                    'error' => "Votre compte n'est pas encore activé. Veuillez patienter.",
                ], 403);
            }
            
            $serverTime = (new \DateTime())->format('d-m-Y');

            return new JsonResponse(['token' => $newToken, 'serverTime' => $serverTime]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors du décodage du token', 'details' => $e->getMessage()], 401);
        }
    }
}
