<?php

namespace App\Controller\Recipe;

use App\Entity\Recipe;
use App\Entity\StepOperation;
use App\Repository\RecipeRepository;
use App\Repository\IngredientRepository;
use App\Repository\OperationRepository;
use App\Repository\StepRepository;
use App\Repository\StepOperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\RecipeType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;



final class RecipeCreate extends AbstractController{
    private $doctrine;

    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/recipe/new', name: 'recipe_create')]
    public function create(
        Request $request,
        IngredientRepository $ingredientRepository,
        Recipe $recipe = null,
        EntityManagerInterface $entityManager,
        OperationRepository $operationRepository,
        ): Response
    {
        $recipe = $recipe ?? new Recipe();


        // Récupérer les ingrédients pour la page demandée
        $ingredients = $ingredientRepository->findBy([], ['ingredientName' => 'ASC']);
        // Récuperer les operations par ordre alphabétique
        $operations = $operationRepository->findBy([], ['operationName' => 'ASC']); // ASC pour tri ascendant
        
        $operationsArray = [];
        $ingredientsArray = [];
        $ingredientsPerPage = 8;

        // transformer les ingrédients en tableau pour les envoyer à la vue
        foreach ($ingredients as $ingredient) {
            $ingredientsArray[] = [
                'id' => $ingredient->getId(),
                'ingredientName' => $ingredient->getIngredientName(),
                'image' => $ingredient->getIngredientImg(), // Si applicable
            ];
        }

        // transformer les opérations en tableau pour les envoyer à la vue
        foreach ($operations as $operation) {
            $operationsArray[] = [
                'id' => $operation->getId(),
                'name' => $operation->getOperationName(),
            ];
        }

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            $steps = $recipe->getRecipeSteps();
            $selectedIngredientsJson = $request->request->get('selected-ingredients');
            $selectedIngredients = json_decode($selectedIngredientsJson, true);
            $allSelectedOperationsJson = $request->request->get('all-selected-operations');
            $allSelectedOperations = json_decode($allSelectedOperationsJson, true);

            // dd($allSelectedOperations);

            // Gestion des ingrédients
            if ($selectedIngredients) {
                // Décode les ingrédients sélectionnés
                foreach ($selectedIngredients as $ingredientData) {
                    $ingredientId = $ingredientData['ingredientId'];
                    $quantity = $ingredientData['quantity'];
                    $unit = isset($ingredientData['unit']) ? $ingredientData['unit'] : ''; // Récupérer l'unité, ou vide si non spécifié

                    // Trouver l'ingrédient dans la base de données
                    $ingredient = $ingredientRepository->find($ingredientId);
                    
                    if ($ingredient) {
                        // Ajouter l'ingrédient à la recette
                        $recipe->addRecipeIngredient($ingredient);
                        
                        // Ajouter la quantité et l'unité associée à cet ingrédient
                        $recipe->addIngredientQuantity($ingredientId, $quantity, $unit); // Assurez-vous que cette méthode prend aussi l'unité en paramètre
                    }
                }
            }
            foreach ($steps as $index => $step) {
                $step->setStepNumber($index + 1); // L'ordre commence à 1
                $step->setStepRecipe($recipe);  // Lier la recette à chaque étape
            }

            if ($image) {
                // Renommer l'image avec un nom unique
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = uniqid() . '.' . $image->guessExtension();
    
                try {
                    // Déplacer l'image dans le dossier des assets
                    $image->move(
                        $this->getParameter('kernel.project_dir') . '/public/images/recipes', // Dossier de destination
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer les erreurs d'upload
                    // Par exemple, vous pouvez enregistrer un message d'erreur
                }
    
                // Vous pouvez maintenant enregistrer le nom du fichier dans la base de données si vous en avez besoin
                $recipe->setRecipeImg($newFilename);
            }
            

            // Enregistrer la recette et ses étapes
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($recipe); // Enregistrer la recette
            $entityManager->flush(); // Sauvegarder dans la base de données

            // Recharger les étapes après avoir persisté la recette
            $steps = $recipe->getRecipeSteps();

            // Gestion des opérations
            foreach ($allSelectedOperations as $operationData) {
                dump($operationData);
                // Récupérer l'opération et l'ingrédient
                $operation = $operationRepository->find($operationData['operationId']);
                $ingredient = $ingredientRepository->find($operationData['ingredientId']);
                
                // Si l'opération et l'ingrédient existent, on crée la relation
                if ($operation && $ingredient) {
                   // Trouver l'étape correspondante en fonction de stepIndex
                    $step = null;
                    foreach ($steps as $recipeStep) {
                        if ($recipeStep->getStepNumber() === $operationData['stepIndex']) {
                            $step = $recipeStep;
                            break;
                        }
                    }
                     // Si une étape correspondante est trouvée
                    if ($step) {
                        // Créer une nouvelle StepOperation
                        $stepOperation = new StepOperation();
                        $stepOperation->setStep($step); // Associe l'étape
                        $stepOperation->setOperation($operation); // Associe l'opération
                        $stepOperation->setIngredient($ingredient); // Associe l'ingrédient
                        
                        // Ajouter la StepOperation à l'étape et persister
                        $step->addStepOperation($stepOperation);
                        $entityManager->persist($stepOperation);
                    } else {
                        // Si l'étape n'est pas trouvée
                        dump("Aucune étape trouvée pour stepIndex: " . $operationData['stepIndex']);
                    }
                } else {
                    // Si l'opération ou l'ingrédient n'existent pas
                    dump("Operation ou Ingredient introuvable. operationId: " . $operationData['operationId'] . ", ingredientId: " . $operationData['ingredientId']);
                }
            }
            
            // Enregistrer toutes les modifications
            $entityManager->flush();

            // Redirection après l'ajout
            return $this->redirectToRoute('app_recipe');
        }


        return $this->render('recipe/create.html.twig', [
            'form' => $form->createView(),
            'ingredients' => $ingredientsArray,
            'ingredientsPerPage' => $ingredientsPerPage,
            'operations' => $operationsArray,
            'steps' => $recipe->getRecipeSteps(),
        ]);
    }
    
}