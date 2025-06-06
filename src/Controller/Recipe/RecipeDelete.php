<?php

namespace App\Controller\Recipe;

use App\Entity\Recipe;
use App\Repository\StepOperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Filesystem\Filesystem;


final class RecipeDelete extends AbstractController{
    private $doctrine;

    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/recipe/delete/{id}', name: 'recipe_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager, StepOperationRepository $stepOpRepo): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous n\'avez pas les droits pour supprimer une recette.');
            return $this->redirectToRoute('app_home');
        }

        // Vérification du token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_recipe', $submittedToken))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $recipe = $entityManager->getRepository(Recipe::class)->find($id);
        
        if ($recipe){

            // Supprimer l'image stockée
            $filesystem = new Filesystem();
            $recipeImage = $recipe->getRecipeImg();
            if ($recipeImage) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/recipes/' . $recipeImage;
                
                if ($filesystem->exists($imagePath)) {
                    $filesystem->remove($imagePath);
                    // $this->addFlash('success', "L'image a été supprimée.");
                }
            }

            //Recuperer les etapes de la recette
            $steps = $recipe->getRecipeSteps();

            //Trouver les opérations de chaque étape et les supprimer
            foreach ($steps as $recipeStep) {
                $stepId = $recipeStep->getId();
                // dump($stepId);
                // lister les stepOperations par étapoe
                $stepOperations = $stepOpRepo->findByStepId($stepId);
                foreach ($stepOperations as $stepOperation){
                    // Supprimer chaque stepOperation listé
                    $entityManager->remove($stepOperation);
                }
            }
            $entityManager->flush();
        } else {
            throw $this->createNotFoundException('Recette non trouvée');
        }

        // Supprimer la recette (les steps associés serront supprimés en cascade)
        if ($this->isGranted('ROLE_ADMIN')) {
            $entityManager->remove($recipe);
        }
        // Si l'utilisateur n'est pas admin, il ne peut pas supprimer la recette
        else {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour supprimer cette recette.');
        }
        $entityManager->flush();

        // Message flash de suppression
        $this->addFlash('success', 'Recette supprimée avec succès.');

        return $this->redirectToRoute('app_recipe', [], 303);
    }
    
}