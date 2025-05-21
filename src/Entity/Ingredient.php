<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ingredient:read', 'recipe:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ingredient:read','recipe:read'])]
    private ?string $ingredientName = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['ingredient:read'])]
    private array $ingredientUnit = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ingredient:read', 'recipe:read'])]
    private ?string $ingredientImg = null;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\ManyToMany(targetEntity: Recipe::class, mappedBy: 'recipeIngredient', fetch: 'LAZY')]
    #[Ignore]
    private Collection $ingredientRecipe;

    /**
     * @var Collection<int, StepOperation>
     */
    #[ORM\OneToMany(targetEntity: StepOperation::class, mappedBy: 'ingredient', orphanRemoval: true, cascade: ['persist', 'remove'], fetch: 'LAZY')]
    #[Ignore]
    private Collection $stepOperations;

    /**
     * @var Collection<int, Fridge>
     */
    #[ORM\ManyToMany(targetEntity: Fridge::class, mappedBy: 'ingredients', fetch: 'LAZY')]
    #[Ignore]
    private Collection $fridges;


    public function __construct()
    {
        $this->ingredientRecipe = new ArrayCollection();
        $this->stepOperations = new ArrayCollection();
        $this->fridges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIngredientName(): ?string
    {
        return $this->ingredientName;
    }

    public function setIngredientName(string $ingredientName): static
    {
        $this->ingredientName = $ingredientName;

        return $this;
    }

    /**
     * @see UserInterface
     * @return list<string>
     */
    public function getIngredientUnit(): array
    {
        return $this->ingredientUnit ?? [];
    }

    /**
     * @param list<string> $ingredientUnit
     */
    public function setIngredientUnit(array $ingredientUnit): static
    {
        $this->ingredientUnit = $ingredientUnit;

        return $this;
    }

    public function getIngredientUnitDisplay(): string
    {
        if (empty($this->ingredientUnit)) {
            return '';
        }

        return implode(', ', array_map(function ($unit) {
            return ($unit === '' || $unit === ' ') ? '(pièce)' : $unit;
        }, $this->ingredientUnit));
    }

    public function getIngredientImg(): ?string
    {
        return $this->ingredientImg;
    }

    public function setIngredientImg(?string $ingredientImg): static
    {
        $this->ingredientImg = $ingredientImg;

        return $this;
    }

    /**
     * @return Collection<int, Recipe>
     */
    public function getIngredientRecipe(): Collection
    {
        return $this->ingredientRecipe;
    }

    public function addIngredientRecipe(Recipe $ingredientRecipe): static
    {
        if (!$this->ingredientRecipe->contains($ingredientRecipe)) {
            $this->ingredientRecipe->add($ingredientRecipe);
            $ingredientRecipe->addRecipeIngredient($this);
        }

        return $this;
    }

    public function removeIngredientRecipe(Recipe $ingredientRecipe): static
    {
        if ($this->ingredientRecipe->removeElement($ingredientRecipe)) {
            $ingredientRecipe->removeRecipeIngredient($this);
        }

        return $this;
    }
    
    /**
     * @return Collection<int, StepOperation>
     */
    public function getStepOperations(): Collection
    {
        return $this->stepOperations;
    }

    public function addStepOperation(StepOperation $stepOperation): self
    {
        if (!$this->stepOperations->contains($stepOperation)) {
            $this->stepOperations->add($stepOperation);
            $stepOperation->setIngredient($this);
        }

        return $this;
    }

    public function removeStepOperation(StepOperation $stepOperation): self
    {
        if ($this->stepOperations->removeElement($stepOperation)) {
            if ($stepOperation->getIngredient() === $this) {
                $stepOperation->setIngredient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Fridge>
     */
    public function getFridges(): Collection
    {
        return $this->fridges;
    }

    public function addFridge(Fridge $fridge): static
    {
        if (!$this->fridges->contains($fridge)) {
            $this->fridges->add($fridge);
            $fridge->addIngredient($this);
        }

        return $this;
    }

    public function removeFridge(Fridge $fridge): static
    {
        if ($this->fridges->removeElement($fridge)) {
            $fridge->removeIngredient($this);
        }

        return $this;
    }

}
