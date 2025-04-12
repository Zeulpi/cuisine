<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Repository\UserRepository;

final class UserListController extends AbstractController{
    private $doctrine;
    
    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/users/list', name: 'app_users', methods: ['GET', 'POST'])]
    public function listUsers(Request $request, UserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        // Vérification des droits
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        // Récupération des paramètres pour la pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 20)); // Limite par défaut 10
        $role = array_filter(explode(',', $request->query->get('roles') ?? ''));
        $orderBy = $request->query->get('orderby');
        $allowedSortFields = ['id', 'email', 'username', 'roles'];
        $order = strtolower($request->query->get('order', 'asc'));

        
        if (empty($order) || !in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }

        // Construction de la requête de base
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->orderBy('u.email', $order);


        // Appliquer les filtres de rôle
        if (!empty($role)) {
            if (in_array('none', $role)) {
                // dd($role);
                $queryBuilder->andWhere("u.roles = '[]'");
            }
            $actualRoles = array_filter($role, fn($r) => $r !== 'none');
            if (!empty($actualRoles)) {
                foreach ($actualRoles as $index => $r) {
                    $queryBuilder->andWhere("u.roles LIKE :role$index")
                        ->setParameter("role$index", '%"' . $r . '"%');
                }
            }
        }

        // Appliquer les autres filtres et tri
        if (!empty($orderBy) && in_array($orderBy, $allowedSortFields)) {
            $queryBuilder->addOrderBy('u.' . $orderBy, $order);
        }

        // Pagination
        $pagination = $paginator->paginate($queryBuilder, $page, $limit);

        // Vérifier si chaque utilisateur a des planners, sinon les initialiser
        $entityManager = $this->doctrine->getManager();
        foreach ($pagination->getItems() as $user) {
            if (empty($user->getUserPlanners())) {
                // Initialiser les planners si l'utilisateur n'en a pas
                $user->setUserPlanners($user->initializePlanners());
                // Sauvegarder l'utilisateur avec ses planners
                $entityManager->persist($user);
            }
        }
        // dd('fini');
        // Sauvegarder tous les utilisateurs avec leurs planners en une seule requête
        $entityManager->flush();

        // Si la requête est une requête Ajax, renvoyer uniquement le tableau
        if ($request->isXmlHttpRequest()) {
            // dd('requete asynchrone');
            return $this->render('users/_user_table.html.twig', [
                'pagination' => $pagination, // Nous renvoyons uniquement le tableau sans pagination
            ]);
        }

        // Sinon, renvoyer la page complète avec pagination
        return $this->render('users/list_users.html.twig', [
            'pagination' => $pagination, // Nous envoyons la page complète avec la pagination
        ]);
    }
}
