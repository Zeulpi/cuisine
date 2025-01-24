<?php

namespace App\Controller\Recipe;

use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use App\Form\RecipeType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


final class RecipeDelete extends AbstractController{
    private $doctrine;

    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/recipe/delete/{id}', name: 'recipe_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Vérification du token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_recipe', $submittedToken))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $recipe = $entityManager->getRepository(Recipe::class)->find($id);
        if (!$recipe) {
            throw $this->createNotFoundException('Recette non trouvée');
        }

        
        // Supprimer la recette
        $entityManager->remove($recipe);
        $entityManager->flush();

        // MEssage flash de suppression
        $this->addFlash('success', 'Recette supprimée avec succès.');

        return $this->redirectToRoute('app_recipe');
    }
    
}