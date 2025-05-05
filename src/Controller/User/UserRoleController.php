<?php
// src/Controller/User/UserRoleController.php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserRoleController extends AbstractController
{
    #[Route('/users/role/update', name: 'api_user_role_update', methods: ['POST'])]
    public function updateUserRole(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
        
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;
        $action = $data['action'] ?? null;

        if (!$userId || !$action) {
            return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
        }

        $user = $userRepository->find($userId);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof \App\Entity\User && $currentUser->getId() === $user->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre rôle.'
            ], 403);
        }

        $roles = $user->getRoles();
        $originalRoles = $roles; // juste pour debug éventuel

        if ($action === 'upgrade') {
            switch ($roles) {
                case []:
                    $user->setRoles(['ROLE_USER']);
                    break;
                case ['ROLE_USER']:
                    $user->setRoles(['ROLE_USER', 'ROLE_CREATOR']);
                    break;
                case ['ROLE_USER', 'ROLE_CREATOR']:
                    $user->setRoles(['ROLE_USER', 'ROLE_CREATOR', 'ROLE_ADMIN']);
                    break;
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Déjà administrateur']);
                    break;
            }
        }

        elseif ($action === 'downgrade') {
            switch ($roles) {
                case ['ROLE_USER']:
                    $user->setRoles([]);
                    break;
                case ['ROLE_USER', 'ROLE_CREATOR']:
                    $user->setRoles(['ROLE_USER']);
                    break;
                case ['ROLE_USER', 'ROLE_CREATOR', 'ROLE_ADMIN']:
                    $user->setRoles(['ROLE_USER', 'ROLE_CREATOR']);
                    break;
                default:
                return new JsonResponse(['success' => false, 'message' => 'Impossible de rétrograder']);
                    break;
            }
        }

        // $user->setRoles(array_values($roles)); // array_values pour réindexer proprement
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
