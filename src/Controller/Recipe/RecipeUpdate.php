<?php

namespace App\Controller\Recipe;

use App\Entity\Recipe;
use App\Entity\Step;
use App\Repository\RecipeRepository;
use App\Repository\IngredientRepository;
use App\Repository\OperationRepository;
use App\Entity\StepOperation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Form\RecipeType;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Dom\Entity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


ini_set('max_execution_time', 0); // Désactive temporairement la limite de temps
ini_set('display_errors', 1);
error_reporting(E_ALL);

final class RecipeUpdate extends AbstractController{
    private $doctrine;

    public function __construct(PersistenceManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/recipe/update/{id}', name: 'recipe_update')]
    public function update(
        Request $request,
        IngredientRepository $ingredientRepository,
        int $id,
        EntityManagerInterface $entityManager,
        OperationRepository $operationRepository,
        SerializerInterface $serializer,
        TokenStorageInterface $tokenStorage
        ): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous devez être connecté pour modifier une recette.');
            return $this->redirectToRoute('app_home');
        }

        $recipe = $entityManager->getRepository(Recipe::class)->find($id);
        if (!$recipe) {
            throw $this->createNotFoundException("Recette non trouvée !");
        }
        // $recipe->getRecipeSteps();
        // $entityManager->refresh($recipe);

        // $stepsFromDb = $entityManager->getRepository(Step::class)
        //     ->findBy(['stepRecipe' => $recipe]);

        // dump("Étapes récupérées directement depuis la base :", $stepsFromDb);

        // Récupérer les ingrédients pour la page demandée
        $ingredients = $ingredientRepository->findBy([], ['ingredientName' => 'ASC']);
        // Récuperer les operations par ordre alphabétique
        $operations = $operationRepository->findBy([], ['operationName' => 'ASC']); // ASC pour tri ascendant
        $ingredientsPerPage = 8;
        $steps = $recipe->getRecipeSteps();
        $tags = $recipe->getRecipeTags();
        $stepOperationArray = [];
        $existingSteps = [];
        $existingOperations = [];

        // Recuperer les ingrédients deja présents dans la recette
        $existingIngredients = [];
        foreach ($recipe->getRecipeIngredient() as $ingredient) {
            $existingIngredients[] = $ingredient->getId();
        }

        // Recuperer les étapes déjà présentes dans la recette
        // foreach ($steps as $step) {
        //     $existingSteps[$step->getId()] = $step;
        // }

        foreach ($ingredients as $ingredient) {
            $ingredientsArray[] = [
                'id' => $ingredient->getId(),
                'ingredientName' => $ingredient->getIngredientName(),
                'image' => $ingredient->getIngredientImg(), // Si applicable
            ];
        }

        foreach ($steps as $step) {
            // Recuperer les étapes déjà présentes dans la recette
            $existingSteps[$step->getId()] = $step;
            // Construire le tableau des operations pour chaque step
            $stepOperationArray[$step->getId()] = array_map(function ($stepOperation) {
                return [
                    'id' => $stepOperation->getId(),
                    'operation' => $stepOperation->getOperation() ? $stepOperation->getOperation()->getId() : null,
                    'ingredient' => $stepOperation->getIngredient() ? $stepOperation->getIngredient()->getId() : null,
                    'operationResult' => $stepOperation->getOperationResult(),
                ];
            }, $step->getStepOperations()->toArray());
        }
        // dd($stepOperationArray);
        dump("Avant handleRequest :", $recipe);
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        dump("Toute la requete :", $request->request->all());
        dump("Étapes reçues par Symfony :", $request->request->all('recipe')['recipeSteps'] ?? []);
        dump("Apres handleRequest :", $recipe);

        // if ($form->isSubmitted()) {
        //     dump($form->getExtraData()); // Voir les champs non reconnus par Symfony
        //     dd("Formulaire soumis, affichage des extra fields");
        // }

        if ($form->isSubmitted() && !$form->isValid()) {
            dump($form->getErrors(true)); // Afficher les erreurs détaillées
            dd("Formulaire invalide !");
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            // ---------------------------------------- //
            // Gérer l'upload ou la suppression de l'image
            // ---------------------------------------- //
            $image = $form->get('image')->getData();
            $removeImage = $request->request->get('remove_image');

            $filesystem = new Filesystem();
            $oldImage = $recipe->getRecipeImg(); // Conserver l'ancienne image

            // 🔹 **Cas 1 & 2 : Suppression de l'image actuelle**
            if ($removeImage == "1" && $oldImage) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/recipes/' . $oldImage;
                
                if ($filesystem->exists($imagePath)) {
                    $filesystem->remove($imagePath);
                    $this->addFlash('success', "L'image a été supprimée.");
                }
                
                $recipe->setRecipeImg(null); // Supprimer l’image en base
            }

            // 🔹 **Cas 2 & 3 : Ajout / Remplacement de l'image**
            if ($image) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxFileSize = 5 * 1024 * 1024; // 5 Mo

                if (!in_array($image->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', "Seuls les fichiers JPEG, PNG, GIF et WEBP sont autorisés.");
                } elseif ($image->getSize() > $maxFileSize) {
                    $this->addFlash('error', "L'image est trop lourde (max 5 Mo).");
                } else {
                    $newFilename = uniqid() . '.' . $image->guessExtension();

                    try {
                        $image->move(
                            $this->getParameter('kernel.project_dir') . '/public/images/recipes',
                            $newFilename
                        );
                        $this->addFlash('success', "L'image a été mise à jour.");
                        $recipe->setRecipeImg($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', "Erreur lors de l'upload de l'image.");
                    }
                }
            }

            // 🔹 **Cas 4 : Rien ne change, aucune modification nécessaire**

            
            // foreach ($existingSteps as $step) {
            //     $entityManager->refresh($step);
            // }
            $selectedIngredientsJson = $request->request->all('recipe')['selectedIngredients'] ?? '[]';
            $selectedIngredients = json_decode($selectedIngredientsJson, true);

            $allSelectedOperationsJson = $request->request->get('all-selected-operations', '[]');
            $allSelectedOperations = json_decode($allSelectedOperationsJson, true) ?? [];

            // dump('all operations', $allSelectedOperations);

            $submittedSteps = $request->request->all('recipe')['recipeSteps'] ?? [];
            $submittedStepIds = array_filter(array_column($submittedSteps, 'id')); // Récupère uniquement les IDs existants
            $submittedStepIds = array_map('intval', $submittedStepIds); // S'assure qu'ils sont bien en `int`

            // Récupérer les ingrédients intermédiaires depuis le champ caché `result-ingredients`
            $resultIngredientsJson = $request->request->get('result-ingredients', '[]');
            $resultIngredients = json_decode($resultIngredientsJson, true) ?? [];
            
            dump("📌 Ingrédients intermédiaires chargés :", $resultIngredients);
            dump('Steps soumis : ', $submittedSteps);
            dump('Steps existants : ', $existingSteps);
            $submittedStepsIndexed = [];
            foreach ($submittedSteps as $step) {
                if (!empty($step['id'])) {
                    $submittedStepsIndexed[(int) $step['id']] = $step;
                }
            }
            dump('Steps soumis indexés : ', $submittedStepsIndexed);


            // Gestion des ingredients et des quantités
            if ($selectedIngredients) {
                $selectedIngredientIds = array_column($selectedIngredients, 'ingredientId');
                // Déterminer les ingrédients à supprimer
                $ingredientsToRemove = array_diff($existingIngredients, $selectedIngredientIds);
                // Déterminer les ingrédients à ajouter
                $ingredientsToAdd = array_diff($selectedIngredientIds, $existingIngredients);
                
                // Supprimer les ingrédients non sélectionnés
                foreach ($ingredientsToRemove as $ingredientId) {
                    $ingredient = $ingredientRepository->find($ingredientId);
                    if ($ingredient) {
                        $recipe->removeRecipeIngredient($ingredient);
                    }
                }

                // Ajouter les nouveaux ingrédients sélectionnés
                foreach ($ingredientsToAdd as $ingredientId) {
                    $ingredient = $ingredientRepository->find($ingredientId);
                    if ($ingredient) {
                        $recipe->addRecipeIngredient($ingredient);
                    }
                }
                // Mettre à jour les quantités et unités
                $recipe->setRecipeQuantities([]); // Réinitialiser pour repartir propre
                foreach ($selectedIngredients as $ingredientData) {
                    $ingredientId = $ingredientData['ingredientId'];
                    $quantity = $ingredientData['quantity'];
                    $unit = $ingredientData['unit'] ?? '';

                    $recipe->addIngredientQuantity($ingredientId, $quantity, $unit); // Comme en création
                }
            }

            // Récupérer les étapes après handleRequest()
            $steps = $recipe->getRecipeSteps();
            $stepIndex = 1;
            // Parcourir et mettre à jour stepSimult et stepNumber
            foreach ($steps as $step) {
                // Vérifier si stepSimult est défini dans la requête
                $submittedStepData = $request->request->all('recipe')['recipeSteps'] ?? [];
                
                // Récupérer les données de l'étape soumise correspondant à l'ID de l'étape en base
                $stepId = $step->getId();
                $stepData = array_filter($submittedStepData, fn($s) => isset($s['id']) && (int)$s['id'] === $stepId);

                // Appliquer la valeur de stepSimult (true si coché, false sinon)
                $step->setStepSimult(!empty($stepData) && isset($stepData[array_key_first($stepData)]['stepSimult']));

                // Mise a jour du stepnumber
                $step->setStepNumber($stepIndex);
                $stepIndex++;
                $entityManager->persist($step);
                // dump("Mise à jour stepSimult pour Step ID: " . $step->getId() . " → " . ($step->isStepSimult() ? 'true' : 'false'));
            }
            
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($recipe);
            $entityManager->flush();
            
            // Recharger les étapes après avoir enregistré la recette
            $steps = $recipe->getRecipeSteps();

            //-----------------------//
            // Gestions des opérations
            //-----------------------//
            dump("StepOperations soumis :", $allSelectedOperations);
            foreach ($steps as $step) {
                // dump($step);
                dump("Step ID {$step->getId()} - StepOperations :", $step->getStepOperations());
                foreach ($step->getStepOperations() as $stepOp) {
                    $existingOperations[$stepOp->getId()] = $stepOp;
                }
            }
            // dump("Operations existantes :", $existingOperations);

            // Operations a mettre a jour
            $operationsToUpdate = [];
            $operationsToAdd = [];
            dump("Opérations existantes :", $existingOperations);
            dump("Opérations soumises :", $allSelectedOperations);
            foreach ($allSelectedOperations as &$operationData) {
                $step = null;
                $newResult = null;
                $ingredient = null;

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
                        $operationData['ingredientId'] = null;
                    }
                } elseif ($operationData['ingredientId'] < 0) {
                    // Cas 3 : L'opération utilise un ingrédient intermédiaire mais ne crée pas de nouvel ingrédient
                    $newResult = ['usedIng' => $operationData['ingredientId']];
                    $operationData['ingredientId'] = null;
                }
                $operationData['operationResult'] = $newResult;

                // 🔸 Vérifier si l'opération existe déjà en base
                if(empty($operationData['id'])) {
                    // 🔹 Nouvelle opération
                    $operationsToAdd[] = $operationData;
                } else if (!empty($operationData['id']) && isset($existingOperations[$operationData['id']])) {
                    $existingOp = $existingOperations[$operationData['id']];

                    dump("🔹 Opération existante [ID {$existingOp->getId()}] :", $existingOp);
                    dump("🔸 Opération soumise [ID {$operationData['id']}] :", $operationData);

                    // Comparer chaque champ pour détecter une modification
                    if (
                        $existingOp->getOperation()->getId() !== $operationData['operationId'] ||
                        ($existingOp->getIngredient() ? $existingOp->getIngredient()->getId() : null) !== $operationData['ingredientId'] ||
                        json_encode($existingOp->getOperationResult()) !== json_encode($operationData['operationResult'])
                    ) {
                        // Ajoute uniquement si un changement est détecté
                        $operationsToUpdate[$operationData['id']] = $operationData;
                    }
                }
            }

            dump("operations modifiées :", $allSelectedOperations);

            // Operations a supprimer
            $submittedOperationIds = array_column($allSelectedOperations, 'id'); // Récupère uniquement les IDs soumis
            $operationsToRemove = array_diff(array_keys($existingOperations), $submittedOperationIds);
            
            dump("Opérations à supprimer :", $operationsToRemove);
            dump("Opérations à ajouter :", $operationsToAdd);
            dump("📌 Opérations à mettre à jour :", $operationsToUpdate);


            // Suppression des opérations
            foreach ($operationsToRemove as $operationId) {
                $operationToRemove = $existingOperations[$operationId] ?? null;
                if ($operationToRemove) {
                    $entityManager->remove($operationToRemove);
                    dump("❌ Opération supprimée : ", $operationToRemove);
                }
            }

            // Mise à jour des opérations
            foreach ($operationsToUpdate as $operationId => $operationData) {
                $operationToUpdate = $existingOperations[$operationId] ?? null;
                if ($operationToUpdate) {
                    // Gestion de l'opération
                    $operation = array_filter($operations, fn($op) => $op->getId() === (int) $operationData['operationId']);
                    $operationToUpdate->setOperation(reset($operation) ?: null);
                     // Gestion de l'ingrédient
                    if ($operationData['ingredientId'] > 0) {
                        // 🔹 Ingrédient existant : le chercher dans les ingrédients préchargés
                        $ingredient = array_filter($ingredients, fn($ing) => $ing->getId() === (int) $operationData['ingredientId']);
                        $operationToUpdate->setIngredient(reset($ingredient) ?: null);
                    } else {
                        // 🔹 Ingrédient intermédiaire : toujours `null`
                        $operationToUpdate->setIngredient(null);
                    }
                    // Gestion du résultat d'opération
                    $operationData['operationResult'] ? $operationToUpdate->setOperationResult($operationData['operationResult']) : null;
            
                    $entityManager->persist($operationToUpdate);
                    dump("📝 Opération mise à jour : ", $operationToUpdate);
                }
            }


            // 🔹 AJOUTER LES NOUVELLES OPÉRATIONS
            foreach ($operationsToAdd as $operationData) {
                // Trouver l’étape correspondante
                $step = $recipe->getRecipeSteps()[$operationData['stepIndex']] ?? null;
                if (!$step) {
                    dump("⚠ Étape non trouvée pour l'opération :", $operationData);
                    continue;
                }

                // Trouver l’opération dans la liste existante
                $operation = array_filter($operations, fn($op) => $op->getId() === (int) $operationData['operationId']);
                $operation = reset($operation) ?: null;

                if (!$operation) {
                    dump("⚠ Opération non trouvée :", $operationData);
                    continue;
                }

                // Trouver l’ingrédient si > 0, sinon NULL pour ingrédient intermédiaire
                $ingredient = $operationData['ingredientId'] > 0
                    ? array_filter($ingredients, fn($ing) => $ing->getId() === (int) $operationData['ingredientId'])
                    : null;
                $ingredient = $ingredient ? reset($ingredient) : null;

                // Gérer l'opérationResult (ingrédient transformé)
                $operationResult = $operationData['operationResult'] ?? null;

                // 🔸 Création de la nouvelle StepOperation
                $newStepOperation = new StepOperation();
                $newStepOperation->setStep($step);
                $newStepOperation->setOperation($operation);
                $newStepOperation->setIngredient($ingredient);
                $operationResult ? $newStepOperation->setOperationResult($operationResult) : null;

                // 🔸 Associer à l’étape
                $step->addStepOperation($newStepOperation);
                $entityManager->persist($newStepOperation);

                dump("✅ Nouvelle opération ajoutée :", $newStepOperation);
            }

            $entityManager->flush();

            // Redirection après l'ajout

            dump("Recette mise à jour avec succès !");
            // dd('fini');
            $this->addFlash('success', 'Recette mise à jour avec succès !');
            return $this->redirectToRoute('app_recipe');
        }

        return $this->render('recipe/create.html.twig', [
            'form' => $form->createView(),
            'recipe' => $recipe,
            'ingredients' => json_decode($serializer->serialize($ingredients, 'json', ['groups' => 'ingredient:read']), true),
            'ingredientsPerPage' => $ingredientsPerPage,
            'operations' => json_decode($serializer->serialize($operations, 'json', ['groups' => 'operations:read']), true),
            'steps' => $steps,
            'tags' => $tags,
            'stepOperations' => $stepOperationArray,
            'isEditMode' => true,
        ]);
    }
}