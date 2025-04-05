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
            if (empty($roles)) {
                $user->setRoles(['ROLE_USER']);
            } elseif ($roles === ['ROLE_USER']) {
                $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            } elseif (!in_array('ROLE_ADMIN', $roles) && in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles(array_unique($roles));
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Déjà administrateur']);
            }
        }

        elseif ($action === 'downgrade') {
            if ($roles === ['ROLE_USER']) {
                $user->setRoles([]);
            } elseif ($roles === ['ROLE_USER', 'ROLE_ADMIN']) {
                $user->setRoles(['ROLE_USER']);
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Impossible de rétrograder']);
            }
        }

        // $user->setRoles(array_values($roles)); // array_values pour réindexer proprement
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
