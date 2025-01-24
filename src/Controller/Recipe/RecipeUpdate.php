<?php

namespace App\Controller\Recipe;

use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use App\Repository\IngredientRepository;
use App\Form\RecipeType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;


final class RecipeUpdate extends AbstractController{
    private $doctrine;

    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/recipe/update/{id}', name: 'recipe_update')]
    public function update(Request $request, RecipeRepository $recipeRepository, int $id, IngredientRepository $ingredientRepository, Recipe $recipe = null): Response
    {
        $recipe = $this->doctrine->getRepository(Recipe::class)->find($id);
        $ingredients = $ingredientRepository->findAll();
        $ingredientsArray = [];
        $existingIngredients = [];
        foreach ($recipe->getRecipeIngredient() as $ingredient) {
            $existingIngredients[] = $ingredient->getId();
        }

        foreach ($ingredients as $ingredient) {
            $ingredientsArray[] = [
                'id' => $ingredient->getId(),
                'ingredientName' => $ingredient->getIngredientName(),
                'image' => $ingredient->getIngredientImg(), // Si applicable
            ];
        }

        if (!$recipe) {
            throw $this->createNotFoundException('Recette non trouvée');
        }
        
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $steps = $recipe->getRecipeSteps();

            $selectedIngredientsJson = $request->request->get('selected-ingredients');
            $selectedIngredients = json_decode($selectedIngredientsJson, true);
            if ($selectedIngredients) {
                // Décode les ingrédients sélectionnés
                foreach ($selectedIngredients as $ingredientId) {
                    $ingredient = $ingredientRepository->find($ingredientId);
                    if ($ingredient) {
                        $recipe->addRecipeIngredient($ingredient);
                    }
                }
            }

            foreach ($steps as $index => $step) {
                $step->setStepNumber($index + 1); // L'ordre commence à 1
                $step->setStepRecipe($recipe);  // Lier la recette à chaque étape
            }
            // Gérer l'upload de l'image
        $image = $form->get('image')->getData();


        if ($image) {
            // Générer un nouveau nom unique pour l'image
            $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = uniqid() . '.' . $image->guessExtension();

            try {
                // Déplacer l'image dans le dossier des assets
                $image->move(
                    $this->getParameter('kernel.project_dir') . '/public/images/recipes', // Dossier de destination
                    $newFilename
                );
            } catch (FileException $e) {
                // Gérer les erreurs d'upload si nécessaire
                // Vous pouvez enregistrer un message d'erreur ou gérer cela comme vous le souhaitez
            }

            // Mettre à jour le champ image avec le nouveau nom de fichier
            $recipe->setRecipeImg($newFilename);
        }
        else {
            // Si aucune nouvelle image n'est téléchargée, conserver l'image existante
            $recipe->setRecipeImg($recipe->getRecipeImg());
        }
            // Enregistrer la recette et ses étapes
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($recipe); // Enregistrer la recette
            $entityManager->flush(); // Sauvegarder dans la base de données

            // Redirection après l'ajout
            return $this->redirectToRoute('app_recipe');
        }

        return $this->render('recipe/update.html.twig', [
            'form' => $form->createView(),
            'recipe' => $recipe,
            'existingIngredients' => json_encode($existingIngredients),
            'ingredients' => $ingredientsArray,
        ]);
    }
    
}