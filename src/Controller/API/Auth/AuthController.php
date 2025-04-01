<?php

// src/Controller/API/Auth/AuthController.php
namespace App\Controller\API\Auth;

use App\Entity\User;
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

    // Injection des services dans le constructeur
    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager, SerializerInterface $serializer)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;  // Attribution de l'EntityManager à la propriété
        $this->jwtManager = $jwtManager;  // Attribution du service JWT à la propriété
        $this->serializer = $serializer;
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request)
    {
        // Récupération des données JSON envoyées
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email et mot de passe requis'], 400);
        }

        // Trouver l'utilisateur par son email
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Vérification du mot de passe avec l'encoder
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Mot de passe incorrect'], 401);
        }

        // Si l'authentification réussit, générer un token JWT
        $token = $this->jwtManager->create($user);  // Génération du token JWT

        // Retourner le token JWT dans la réponse
        return new JsonResponse(['message' => 'Connexion réussie', 'token' => $token]);
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

            return new JsonResponse(['token' => $newToken]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors du décodage du token', 'details' => $e->getMessage()], 401);
        }
    }
}
