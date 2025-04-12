<?php
// src/Controller/User/UserPlannerController.php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserPlannerController extends AbstractController
{
    #[Route('/users/planner/reset', name: 'api_user_planner_reset', methods: ['POST'])]
    public function resetUserPlanner(
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

        // Gestion des différentes actions
        switch ($action) {
            case 'reset':
                $user->resetUserPlanners();
                break;

            default:
                return new JsonResponse(['success' => false, 'message' => 'Action invalide'], 400);
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
