<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ingredientName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ingredientUnit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ingredientImg = null;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\ManyToMany(targetEntity: Recipe::class, mappedBy: 'recipeIngredient')]
    private Collection $ingredientRecipe;

    /**
     * @var Collection<int, StepOperation>
     */
    #[ORM\OneToMany(targetEntity: StepOperation::class, mappedBy: 'ingredient', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $stepOperations;

    public function __construct()
    {
        $this->ingredientRecipe = new ArrayCollection();
        $this->stepOperations = new ArrayCollection();
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

    public function getIngredientUnit(): ?string
    {
        return $this->ingredientUnit;
    }

    public function setIngredientUnit(string $ingredientUnit): static
    {
        $this->ingredientUnit = $ingredientUnit;

        return $this;
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

}
