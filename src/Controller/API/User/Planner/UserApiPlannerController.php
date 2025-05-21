<?php

namespace App\Controller\API\User\Planner;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserApiPlannerController extends AbstractController
{
    private $entityManager;
    private $jwtManager;
    private $serializer;
    private $jwtEncoder;

    // Injection des services dans le constructeur
    public function __construct(EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager, SerializerInterface $serializer, JWTEncoderInterface $jwtEncoder)
    {
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
        $this->serializer = $serializer;
        $this->jwtEncoder = $jwtEncoder;
    }

    // #[Route('/api/user/planner/addrecipe', name: 'api_user_planner_add', methods: ['POST'])]
    public function addRecipeToPlanner(Request $request, EntityManagerInterface $entityManager) : JsonResponse
    {
        try {
            // Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);

            $receivedData = [
                "sentToken" => $data['token'],
                "sentIndex" => $data['index'],
                "sentDay" => $data['day'],
                "sentRecipeId" => $data['recipeId'],
                "sentPortions" => $data['portions'],
            ];

            if (!$receivedData['sentToken'] || !$receivedData['sentDay'] || !$receivedData['sentRecipeId'] || !$receivedData['sentPortions']) {
                return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
            }

            if (!is_int($receivedData['sentIndex']) || $receivedData['sentIndex'] < 0 || $receivedData['sentIndex'] > 3) {
                return new JsonResponse(['error' => 'Index planner invalide'], 400);
            }

            if (!is_int($receivedData['sentRecipeId']) || $receivedData['sentRecipeId'] <= 0) {
                return new JsonResponse(['error' => 'ID de recette invalide'], 400);
            }

            if (!is_int($receivedData['sentPortions']) || $receivedData['sentPortions'] <= 0) {
                return new JsonResponse(['error' => 'Le nombre de portions doit être supérieur à zéro'], 400);
            }

            try {
                $payload = $this->jwtEncoder->decode($receivedData["sentToken"]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Token invalide',
                    'details' => $e->getMessage(),
                ], 401);
            }
            // Trouver l'utilisateur par son email
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);

            // Verifier si le User existe
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }
            // Verifier le token pour s'assurer que le bon user accede au bon planner
            if (!$payload || $payload['email'] !== $user->getUserIdentifier()) {
                return new JsonResponse(['error' => 'Token invalide ou utilisateur non autorisé'], 401);
            }
            
            
            // Récupérer le planner actif
            $planner = $user->getOnePlanner(($receivedData['sentIndex']));
            

            // Ajouter ou mettre à jour la recette du jour spécifié
            $planner->addRecipe(($receivedData['sentDay']), ($receivedData['sentRecipeId']), ($receivedData['sentPortions']));

            // Placer la recette mise à jour dans le tableau des planners de l'utilisateur sans écraser les autres
            $user->setActivePlanner($planner, ($receivedData['sentIndex']));

            // Sauvegarder les changements
            $entityManager->persist($user);
            $entityManager->flush();
            
            // Raffraichir $user et générer un nouveau token
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);
            $newToken = $this->jwtManager->create($user);

            $allRecipeIds = $user->getAllRecipesIds();
            // return new JsonResponse(['message' => 'Test', 'token' => $newToken, 'planner' => $recipes]);
            

            $query = $entityManager->createQueryBuilder()
                ->select('r')
                ->from('App\Entity\Recipe', 'r')
                ->where('r.id IN (:ids)')
                ->setParameter('ids', $allRecipeIds)
                ->getQuery();
            $recipesArray = $query->getResult();

            $recipes = [];
            foreach ($recipesArray as $recipe) {
                $recipes[$recipe->getId()] = [
                    'id' => $recipe->getId(),
                    'name' => $recipe->getRecipeName(),
                    'image' => $recipe->getRecipeImg(),
                    'duration' => (function () use ($recipe) {
                        $steps = $recipe->getRecipeSteps()->toArray();
                        $totalDuration = 0;
                        $currentBlock = [];

                        foreach ($steps as $index => $step) {
                            $time = $step->getStepTime() ?? 0;
                            $unit = strtolower($step->getStepTimeUnit() ?? 'minutes');

                            $durationInMinutes = match ($unit) {
                                'heures', 'heure' => $time * 60,
                                'secondes', 'seconde' => $time / 60,
                                default => $time
                            };

                            $currentBlock[] = $durationInMinutes;
                            $nextStep = $steps[$index + 1] ?? null;
                            $nextIsSimultaneous = $nextStep && ($nextStep->isStepSimult() ?? false);

                            if (!$nextIsSimultaneous) {
                                $totalDuration += max($currentBlock);
                                $currentBlock = [];
                            }
                        }

                        return [
                            'value' => (int) round($totalDuration),
                            'unit' => 'minutes'
                        ];
                    })(),
                    'tags' => array_map(fn($tag) => [
                        'name' => $tag->getTagName(),
                        'color' => $tag->getTagColor()
                    ], $recipe->getRecipeTags()->toArray()),
                    'portions' => $recipe->getRecipePortions(),
                ];
            }

            // Retourner le token JWT dans la réponse + la liste des recettes du planner
            return new JsonResponse(['message' => 'Recette ajoutée au planner', 'token' => $newToken, 'recipes' => $recipes]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    // #[Route('/api/user/planner/get', name: 'api_user_planner_get', methods: ['GET'])]
    public function getPlanners(Request $request, EntityManagerInterface $entityManager) : JsonResponse
    {
        try {// Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);
            $sentToken = $request->query->get('token');
            $didPlannerUpdate = false;
            $serverTime = (new \DateTime())->format('d-m-Y'); // Récuperer l'heure actuelle du server


            if (!$sentToken) {
                return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
            }

            try {
                $payload = $this->jwtEncoder->decode($sentToken);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Token invalide',
                    'details' => $e->getMessage(),
                ], 401);
            }
            // Trouver l'utilisateur par son email
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);

            // Verifier si le User existe
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }
            // Verifier le token pour s'assurer que le bon user accede au bon planner
            if (!$payload || $payload['email'] !== $user->getUserIdentifier()) {
                return new JsonResponse(['error' => 'Token invalide ou utilisateur non autorisé'], 401);
            }

            $didPlannerUpdate = $user->updatePlanners(); // Vérifier et mettre a jour les planners expirés (0 et 1)

            // return new JsonResponse(['message' => 'return test', 'planner updated ? ' => $didPlannerUpdate], 200);

            // Sauvegarder les changements des planners expirés / mis a jour
            $entityManager->persist($user);
            $entityManager->flush();
            

            // Raffraichir $user et générer un nouveau token
            // $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);
            $newToken = $this->jwtManager->create($user);


            $allRecipeIds = $user->getAllRecipesIds();            

            $query = $entityManager->createQueryBuilder()
                ->select('r')
                ->from('App\Entity\Recipe', 'r')
                ->where('r.id IN (:ids)')
                ->setParameter('ids', $allRecipeIds)
                ->getQuery();
            $recipesArray = $query->getResult();

            $recipes = [];
            foreach ($recipesArray as $recipe) {
                $recipes[$recipe->getId()] = [
                    'id' => $recipe->getId(),
                    'name' => $recipe->getRecipeName(),
                    'image' => $recipe->getRecipeImg(),
                    'duration' => (function () use ($recipe) {
                        $steps = $recipe->getRecipeSteps()->toArray();
                        $totalDuration = 0;
                        $currentBlock = [];

                        foreach ($steps as $index => $step) {
                            $time = $step->getStepTime() ?? 0;
                            $unit = strtolower($step->getStepTimeUnit() ?? 'minutes');

                            $durationInMinutes = match ($unit) {
                                'heures', 'heure' => $time * 60,
                                'secondes', 'seconde' => $time / 60,
                                default => $time
                            };

                            $currentBlock[] = $durationInMinutes;
                            $nextStep = $steps[$index + 1] ?? null;
                            $nextIsSimultaneous = $nextStep && ($nextStep->isStepSimult() ?? false);

                            if (!$nextIsSimultaneous) {
                                $totalDuration += max($currentBlock);
                                $currentBlock = [];
                            }
                        }

                        return [
                            'value' => (int) round($totalDuration),
                            'unit' => 'minutes'
                        ];
                    })(),
                    'tags' => array_map(fn($tag) => [
                        'name' => $tag->getTagName(),
                        'color' => $tag->getTagColor()
                    ], $recipe->getRecipeTags()->toArray()),
                    'portions' => $recipe->getRecipePortions(),
                ];
            }


            // Retourner le token JWT dans la réponse + la liste des recettes du planner
            return new JsonResponse(['message' => 'Recupération des recettes', 'token' => $newToken, 'recipes' => $recipes, 'updatedExpired' => $didPlannerUpdate, 'serverTime' => $serverTime]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    // #[Route('/api/user/planner/removerecipe', name: 'api_user_planner_remove', methods: ['POST'])]
    public function removeRecipeFromPlanner(Request $request, EntityManagerInterface $entityManager) : JsonResponse
    {
        try {
            // Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);

            $receivedData = [
                "sentToken" => $data['token'],
                "sentIndex" => $data['index'],
                "sentDay" => $data['day'],
            ];

            if (!$receivedData['sentToken'] ||  !$receivedData['sentDay'] ) {
                return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
            }

            if (!is_int($receivedData['sentIndex']) || $receivedData['sentIndex'] > 3 || $receivedData['sentIndex'] < 0) {
                return new JsonResponse(['success' => false, 'message' => 'Index planner invalide'], 400);
            }

            try {
                $payload = $this->jwtEncoder->decode($receivedData["sentToken"]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Token invalide',
                    'details' => $e->getMessage(),
                ], 401);
            }
            // Trouver l'utilisateur par son email
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);

            // Verifier si le User existe
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }
            // Verifier le token pour s'assurer que le bon user accede au bon planner
            if (!$payload || $payload['email'] !== $user->getUserIdentifier()) {
                return new JsonResponse(['error' => 'Token invalide ou utilisateur non autorisé'], 401);
            }
            
            
            // Récupérer le planner actif
            $planner = $user->getOnePlanner(($receivedData['sentIndex']));
            
            // return new JsonResponse(['message' => 'Recette ajoutée au planner', 'token' => 'token', 'day' => $receivedData['sentDay']]);

            // Enlever la recette du jour spécifié
            $planner->removeRecipe(($receivedData['sentDay']));

            // Placer la recette mise à jour dans le tableau des planners de l'utilisateur sans écraser les autres
            $user->setActivePlanner($planner, $receivedData['sentIndex']);

            // Sauvegarder les changements
            $entityManager->persist($user);
            $entityManager->flush();

            // Raffraichir $user et générer un nouveau token
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);
            $newToken = $this->jwtManager->create($user);

            $allRecipeIds = $user->getAllRecipesIds();

            $query = $entityManager->createQueryBuilder()
                ->select('r')
                ->from('App\Entity\Recipe', 'r')
                ->where('r.id IN (:ids)')
                ->setParameter('ids', $allRecipeIds)
                ->getQuery();
            $recipesArray = $query->getResult();

            $recipes = [];
            foreach ($recipesArray as $recipe) {
                $recipes[$recipe->getId()] = [
                    'id' => $recipe->getId(),
                    'name' => $recipe->getRecipeName(),
                    'image' => $recipe->getRecipeImg(),
                    'duration' => (function () use ($recipe) {
                        $steps = $recipe->getRecipeSteps()->toArray();
                        $totalDuration = 0;
                        $currentBlock = [];

                        foreach ($steps as $index => $step) {
                            $time = $step->getStepTime() ?? 0;
                            $unit = strtolower($step->getStepTimeUnit() ?? 'minutes');

                            $durationInMinutes = match ($unit) {
                                'heures', 'heure' => $time * 60,
                                'secondes', 'seconde' => $time / 60,
                                default => $time
                            };

                            $currentBlock[] = $durationInMinutes;
                            $nextStep = $steps[$index + 1] ?? null;
                            $nextIsSimultaneous = $nextStep && ($nextStep->isStepSimult() ?? false);

                            if (!$nextIsSimultaneous) {
                                $totalDuration += max($currentBlock);
                                $currentBlock = [];
                            }
                        }

                        return [
                            'value' => (int) round($totalDuration),
                            'unit' => 'minutes'
                        ];
                    })(),
                    'tags' => array_map(fn($tag) => [
                        'name' => $tag->getTagName(),
                        'color' => $tag->getTagColor()
                    ], $recipe->getRecipeTags()->toArray()),
                    'portions' => $recipe->getRecipePortions(),
                ];
            }

            // Retourner le token JWT dans la réponse + la liste des recettes du planner
            return new JsonResponse(['message' => 'Recette ajoutée au planner', 'token' => $newToken, 'recipes' => $recipes]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    // #[Route('/api/user/planner/setMark', name: 'api_user_planner_setmark', methods: ['POST'])]
    public function setMark(Request $request, EntityManagerInterface $entityManager) : JsonResponse
    {
        try {
            // Récupération des données JSON envoyées
            $data = json_decode($request->getContent(), true);

            $receivedData = [
                "sentToken" => $data['token'],
                "sentIndex" => $data['index'],
                "sentDay" => $data['day'],
            ];

            if (!$receivedData['sentToken'] ||  !$receivedData['sentDay'] ) {
                return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
            }

            if (!is_int($receivedData['sentIndex']) || $receivedData['sentIndex'] > 3 || $receivedData['sentIndex'] < 0) {
                return new JsonResponse(['success' => false, 'message' => 'Index planner invalide'], 400);
            }

            try {
                $payload = $this->jwtEncoder->decode($receivedData["sentToken"]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Token invalide',
                    'details' => $e->getMessage(),
                ], 401);
            }
            // Trouver l'utilisateur par son email
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);

            // Verifier si le User existe
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }
            // Verifier le token pour s'assurer que le bon user accede au bon planner
            if (!$payload || $payload['email'] !== $user->getUserIdentifier()) {
                return new JsonResponse(['error' => 'Token invalide ou utilisateur non autorisé'], 401);
            }
            
            // Récupérer le planner actif
            $planner = $user->getOnePlanner(($receivedData['sentIndex']));

            //-----------------------------------------------//
            // Traiter la marque pour le planner en question //
            //-----------------------------------------------//
            $planner->setMark($receivedData['sentDay']);

            // mettre a jour le planner en question
            $user->setActivePlanner($planner, ($receivedData['sentIndex']));

            // Sauvegarder les changements
            $entityManager->persist($user);
            $entityManager->flush();

            // Raffraichir $user et générer un nouveau token
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($payload['email']);
            $newToken = $this->jwtManager->create($user);


            // Retourner le token JWT dans la réponse
            return new JsonResponse(['message' => 'Recette ajoutée au planner', 'token' => $newToken]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
