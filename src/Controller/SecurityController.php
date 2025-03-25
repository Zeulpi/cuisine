<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupère l'erreur d'authentification s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        // $error ? dd($error) : null;

        // Dernier nom d'utilisateur saisi (pour préremplir le champ)
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/is_authenticated', name: 'is_authenticated', methods: ['GET'])]
    public function isAuthenticated(): JsonResponse
    {
        // Vérifie si l'utilisateur est authentifié
        $user = $this->getUser();

        if ($user) {
            // Si l'utilisateur est authentifié, renvoie une réponse 200
            return new JsonResponse(['authenticated' => true], 200);
        }

        // Si l'utilisateur n'est pas authentifié, renvoie une réponse 401
        return new JsonResponse(['authenticated' => false], 401);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        // Symfony gère tout automatiquement ici
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
