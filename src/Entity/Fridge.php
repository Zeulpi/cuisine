<?php

namespace App\Entity;

use App\Repository\FridgeRepository;
use App\Repository\IngredientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FridgeRepository::class)]
class Fridge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'fridge')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    /**
     * @var Collection<int, Ingredient>
     */
    #[ORM\ManyToMany(targetEntity: Ingredient::class, inversedBy: 'fridges')]
    private Collection $ingredients;

    #[ORM\Column(nullable: true)]
    private ?array $inventory = [];

    public function __construct()
    {
        $this->ingredients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Ingredient>
     */
    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    public function addIngredient(Ingredient $ingredient): static
    {
        if (!$this->ingredients->contains($ingredient)) {
            $this->ingredients->add($ingredient);
        }

        return $this;
    }

    public function removeIngredient(Ingredient $ingredient): static
    {
        $this->ingredients->removeElement($ingredient);

        return $this;
    }

    public function getInventory(): ?array
    {
        return $this->inventory;
    }

    public function setInventory(?array $inventory): static
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function addIngredientToInventory(int $ingredientId, float $quantity, string $unit, EntityManagerInterface $entityManager): static
    {
        // Récupérer l'ingrédient à partir de son ID
        $ingredientRepository = $entityManager->getRepository(Ingredient::class);
        $ingredient = $ingredientRepository->find($ingredientId);
        if (!$ingredient) {
            throw new \Exception('Ingrédient introuvable');
        }

        // Si l'ingrédient existe déjà dans l'inventaire
        if (isset($this->inventory[$ingredientId])) {
            // Parcourir les quantités existantes de cet ingrédient
            foreach ($this->inventory[$ingredientId] as &$entry) {
                // Si l'unité correspond, on ajoute la quantité à l'existante
                if ($entry['unit'] === $unit) {
                    $entry['quantity'] += $quantity;

                    // Si la quantité devient inférieure ou égale à 0, on retire l'ingrédient
                    if ($entry['quantity'] <= 0) {
                        // Supprimer l'entrée de l'inventaire
                        $this->removeIngredientFromInventory($ingredientId, $unit, $entityManager);
                    }
                    return $this; // Sortir après avoir mis à jour la quantité
                }
            }
            // Si aucune entrée avec la même unité n'a été trouvée, on ajoute une nouvelle entrée
            if($quantity > 0) {
                $this->inventory[$ingredientId][] = ['name' => $ingredient->getIngredientName(), 'image' => $ingredient->getIngredientImg(), 'quantity' => $quantity, 'unit' => $unit, 'allowedUnits' => $ingredient->getIngredientUnit()];
            }
        }
        // Si l'ingrédient n'existe pas encore dans l'inventaire, on l'ajoute (a condition que la quantité > 0)
        elseif (!(isset($this->inventory[$ingredientId])) && $quantity > 0) {
            // Lier le Frigo a l'Ingredient
            $this->addIngredient($ingredient);
            // Si l'ingrédient n'existe pas encore, on le crée avec la première entrée
            $this->inventory[$ingredientId] = [['name'=>$ingredient->getIngredientName(), 'image' => $ingredient->getIngredientImg(), 'quantity' => $quantity, 'unit' => $unit, 'allowedUnits' => $ingredient->getIngredientUnit()]];
        } else {
            return $this;    
        }
        return $this;
    }

    public function removeIngredientFromInventory(int $ingredientId, string $unit, EntityManagerInterface $entityManager): static
    {
        // Vérifier si l'ingrédient existe dans l'inventaire
        if (isset($this->inventory[$ingredientId])) {
            // Parcourir les entrées de l'ingrédient
            foreach ($this->inventory[$ingredientId] as $key => $entry) {
                // Si l'unité correspond, on supprime l'entrée
                if ($entry['unit'] === $unit) {
                    unset($this->inventory[$ingredientId][$key]); // Supprimer l'entrée avec cette unité
                    // Réindexer l'array après la suppression
                    $this->inventory[$ingredientId] = array_values($this->inventory[$ingredientId]);
                    break; // Sortir après avoir trouvé et supprimé l'entrée
                }
            }

            // Vérifier si l'ingrédient a d'autres entrées avec des unités différentes
            if (empty($this->inventory[$ingredientId])) {
                // Si aucune entrée restante pour cet ingrédient, on supprime l'ingrédient de l'inventaire
                unset($this->inventory[$ingredientId]);

                // Si l'ingrédient n'a plus d'entrées dans l'inventaire, on retire la liaison avec cet ingrédient
                $ingredientRepository = $entityManager->getRepository(Ingredient::class);
                $ingredient = $ingredientRepository->find($ingredientId);
                $this->removeIngredient($ingredient);  // Supprimer la liaison avec le frigo
            }
        }
        return $this;
    }

}
