<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Tool;
use App\Entity\Ingredient;
use App\Entity\Operation;
use App\Entity\Recipe;
use App\Entity\Step;
use App\Entity\Tag;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;



class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        
         $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
         return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Back');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('User', 'fas fa-list', User::class);
        yield MenuItem::linkToCrud('Ustensiles', 'fas fa-list', Tool::class);
        yield MenuItem::linkToCrud('Ingrédients', 'fas fa-list', Ingredient::class);
        yield MenuItem::linkToCrud('Recettes', 'fas fa-list', Recipe::class);
        yield MenuItem::linkToCrud('Etapes', 'fas fa-list', Step::class);
        yield MenuItem::linkToCrud('Operation', 'fas fa-list', Operation::class);
        yield MenuItem::linkToCrud('Tags', 'fas fa-list', Tag::class);
    }
}
