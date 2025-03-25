<?php

namespace App\Controller\Recipe;

use App\Entity\Recipe;
use App\Entity\Step;
use App\Entity\StepOperation;
use App\Entity\Operation;
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
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;



final class RecipeCreate extends AbstractController{
    private $doctrine;

    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/recipe/error', name: 'recipe_error')]
    public function errorPage(Request $request): Response
    {
        // Récupérer un message d'erreur passé via une redirection
        $errorMessage = $request->query->get('message', "Une erreur inconnue est survenue.");
        
        return $this->render('recipe/error.html.twig', [
            'errorMessage' => $errorMessage,
        ]);
    }

    #[Route('/recipe/new', name: 'recipe_create')]
    public function create(
        Request $request,
        IngredientRepository $ingredientRepository,
        EntityManagerInterface $entityManager,
        OperationRepository $operationRepository,
        SerializerInterface $serializer,
        ): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous devez être connecté pour modifier une recette.');
            return $this->redirectToRoute('app_home');
        }
        
        $recipe_raw = $recipe_raw ?? new Recipe();

        // Récupérer les ingrédients pour la page demandée
        $ingredients = $ingredientRepository->findBy([], ['ingredientName' => 'ASC']);
        // Récuperer les operations par ordre alphabétique
        $operations = $operationRepository->findBy([], ['operationName' => 'ASC']); // ASC pour tri ascendant
        $ingredientsPerPage = 8;

        $form = $this->createForm(RecipeType::class, $recipe_raw);
        $form->handleRequest($request);

        // if ($form->isSubmitted()) {
        //     dump($form->getExtraData()); // Voir les champs non reconnus par Symfony
        //     dd("Formulaire soumis, affichage des extra fields");
        // }

        if ($form->isSubmitted() && $form->isValid()) {
            dump($request->request->all()); // Voir tous les champs envoyés
            try {
                $image = $form->get('image')->getData();
                $steps = $recipe_raw->getRecipeSteps();
                
                $selectedIngredientsJson = $request->request->all('recipe')['selectedIngredients'] ?? '[]';
                $selectedIngredients = json_decode($selectedIngredientsJson, true);
                $resultIngredientsJson = $request->request->get('result-ingredients', '[]');
                $resultIngredients = json_decode($resultIngredientsJson, true) ?? [];
                $allSelectedOperationsJson = $request->request->get('all-selected-operations', '[]');
                $allSelectedOperations = json_decode($allSelectedOperationsJson, true) ?? [];
                

                // dump($request->request->all('recipe'));
                // dd('fini');
                dump($selectedIngredients);

                //Gestion des ingrédients
                if ($selectedIngredients) {
                    // Décode les ingrédients sélectionnés
                    foreach ($selectedIngredients as $ingredientData) {
                        $ingredientId = $ingredientData['ingredientId'];
                        $quantity = $ingredientData['quantity'];
                        $unit = isset($ingredientData['unit']) ? $ingredientData['unit'] : ''; // Récupérer l'unité, ou vide si non spécifié

                        // Trouver l'ingrédient dans la base de données
                        $ingredient = $ingredientRepository->find($ingredientId);
                        dump($ingredientId);
                        dump($quantity);
                        dump($unit);
                        
                        if ($ingredient) {
                            // Ajouter l'ingrédient à la recette
                            $recipe_raw->addRecipeIngredient($ingredient);
                            
                            // Ajouter la quantité et l'unité associée à cet ingrédient
                            $recipe_raw->addIngredientQuantity($ingredientId, $quantity, $unit); // Assurez-vous que cette méthode prend aussi l'unité en paramètre
                        }
                    }
                }

                // dd('fini');

                // dd($request->request->all());

                //Gestion des Etapes
                foreach ($steps as $index => $step) {
                    $step->setStepNumber($index + 1); // L'ordre commence à 1
                    $step->setStepRecipe($recipe_raw);  // Lier la recette à chaque étape
                    dump($step);
                }

                // Gestion de l'image (thumbnail de recette)
                if ($image) {
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $maxFileSize = 5 * 1024 * 1024; // 5 Mo

                    // Vérification du type MIME
                    if (!in_array($image->getMimeType(), $allowedMimeTypes)) {
                        $this->addFlash('error', "Le fichier sélectionné n'est pas une image valide (JPEG, PNG, GIF, WEBP uniquement).");
                    }
                    // Vérification de la taille
                    elseif ($image->getSize() > $maxFileSize) {
                        $this->addFlash('error', "L'image est trop lourde. Taille maximale : 5 Mo.");
                    }
                    // Si toutes les conditions sont bonnes, on procède à l'upload
                    else {
                        $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                        $newFilename = uniqid() . '.' . $image->guessExtension();

                        try {
                            $image->move(
                                $this->getParameter('kernel.project_dir') . '/public/images/recipes', // Dossier de destination sécurisé
                                $newFilename
                            );
                            $recipe_raw->setRecipeImg($newFilename); // ✅ Enregistrer le nom de l'image en base
                        } catch (FileException $e) {
                            $this->addFlash('error', "Une erreur est survenue lors de l'upload de l'image.");
                        }
                    }
                }
                

                // Enregistrer la recette et ses étapes
                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($recipe_raw); // Enregistrer la recette
                $entityManager->flush(); // Sauvegarder dans la base de données

                // dd('fini');
                
                // Recharger les étapes après avoir enregistré la recette
                $steps = $recipe_raw->getRecipeSteps();

                // Gestion des opérations
                foreach ($allSelectedOperations as $operationData) {
                    $step = null;
                    $newResult = null;
                    $ingredient = null;
                    $operation = $operationRepository->find($operationData['operationId']);

                    // Trouver l'étape correspondante en fonction de stepIndex
                    foreach ($steps as $recipeStep) {
                        if ($recipeStep->getStepNumber() === $operationData['stepIndex']) {
                            $step = $recipeStep;
                            break;
                        }
                    }
                    // Si l'étape ou l'operation ne sont pas trouvées
                    if (!$operation || !$step) {
                        dump("Opération ou étape introuvable");
                        continue;
                    }

                    // Gestion des ingrédients
                    if ($operationData['ingredientId'] > 0) {
                        // Cas 1 et 2 : L'opération utilise un ingrédient de la base
                        $ingredient = $ingredientRepository->find($operationData['ingredientId']);
                    } else {
                        // Cas 3 et 4 : L'opération utilise un ingrédient intermédiaire
                        foreach ($resultIngredients as $resultIng) {
                            if ($operationData['ingredientId'] === $resultIng['resultId']) {
                                $ingredient = $resultIng;
                                break;
                            }
                        }
                    }

                    // Gestion du résultat d'opération
                    if ($operationData['operationResult']) {
                        foreach ($resultIngredients as $resultIng) {
                            if ($operationData['operationResult'] === $resultIng['resultId']) {
                                $newResult = $resultIng;
                                break;
                            }
                        }

                        // Cas 4 : L'opération utilise un ingrédient intermédiaire et génère un nouvel ingrédient intermédiaire
                        if ($newResult && $operationData['ingredientId'] < 0) {
                            $newResult['usedIng'] = $operationData['ingredientId'];
                        }
                    } elseif ($operationData['ingredientId'] < 0) {
                        // Cas 3 : L'opération utilise un ingrédient intermédiaire mais ne crée pas de nouvel ingrédient
                        $newResult = ['usedIng' => $operationData['ingredientId']];
                    }

                    // Créer une stepOperation et ajuster ses champs
                    if ($step){
                        $stepOperation = new StepOperation();
                        $stepOperation->setStep($step); // Associe l'étape
                        $stepOperation->setOperation($operation); // Associe l'opération
                        
                        if ($operationData['ingredientId'] > 0) {
                            // Cas 1 et 2 : L'opération utilise un ingrédient existant
                            $stepOperation->setIngredient($ingredient);
                            if ($newResult) {
                                // Cas 2 : L'opération crée un ingrédient intermédiaire
                                $stepOperation->setOperationResult($newResult);
                            }
                        } elseif ($operationData['ingredientId'] < 0) {
                            // Cas 3 et 4 : L'opération utilise un ingrédient intermédiaire
                            $stepOperation->setOperationResult($newResult);
                        }
                        
                        // Ajouter la StepOperation à l'étape et persister
                        $step->addStepOperation($stepOperation);
                        $entityManager->persist($stepOperation);
                    }
                }
                //dd('fin');
                // Enregistrer toutes les modifications
                $entityManager->flush();

                // Redirection après l'ajout
                return $this->redirectToRoute('app_recipe');
            } catch (\Throwable $e) {
                // Déterminer un message d'erreur générique selon l'erreur
                $errorMessage = "Erreur de gestion des données."; // Par défaut
                
                if ($this->getParameter('kernel.environment') === 'dev') {
                    $errorMessage = "Erreur : " . $e->getMessage(); // Afficher le message exact en mode dev
                }
        
                return $this->redirectToRoute('recipe_error', [
                    'message' => $errorMessage,
                ]);
            }
        }


        return $this->render('recipe/create.html.twig', [
            'form' => $form->createView(),
            'ingredients' => json_decode($serializer->serialize($ingredients, 'json', ['groups' => 'ingredient:read']), true),
            'ingredientsPerPage' => $ingredientsPerPage,
            'operations' => json_decode($serializer->serialize($operations, 'json', ['groups' => 'operations:read']), true),
            'steps' => $recipe_raw->getRecipeSteps(),
        ]);
    }
    
}