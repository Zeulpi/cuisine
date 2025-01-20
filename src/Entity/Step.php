<?php

namespace App\Entity;

use App\Repository\StepRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StepRepository::class)]
class Step
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $stepNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $stepText = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $stepTime = null;

    #[ORM\Column(length: 255)]
    private ?string $stepTimeUnit = null;

    #[ORM\Column]
    private ?bool $stepSimult = null;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\ManyToMany(targetEntity: Recipe::class, mappedBy: 'recipeStep')]
    private Collection $stepRecipe;

    /**
     * @var Collection<int, Ingredient>
     */
    #[ORM\ManyToMany(targetEntity: Ingredient::class, mappedBy: 'ingredientStep')]
    private Collection $stepIngredient;

    public function __construct()
    {
        $this->stepRecipe = new ArrayCollection();
        $this->stepIngredient = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStepNumber(): ?int
    {
        return $this->stepNumber;
    }

    public function setStepNumber(int $stepNumber): static
    {
        $this->stepNumber = $stepNumber;

        return $this;
    }

    public function getStepText(): ?string
    {
        return $this->stepText;
    }

    public function setStepText(?string $stepText): static
    {
        $this->stepText = $stepText;

        return $this;
    }

    public function getStepTime(): ?int
    {
        return $this->stepTime;
    }

    public function setStepTime(int $stepTime): static
    {
        $this->stepTime = $stepTime;

        return $this;
    }

    public function getStepTimeUnit(): ?string
    {
        return $this->stepTimeUnit;
    }

    public function setStepTimeUnit(string $stepTimeUnit): static
    {
        $this->stepTimeUnit = $stepTimeUnit;

        return $this;
    }

    public function isStepSimult(): ?bool
    {
        return $this->stepSimult;
    }

    public function setStepSimult(bool $stepSimult): static
    {
        $this->stepSimult = $stepSimult;

        return $this;
    }

    /**
     * @return Collection<int, Recipe>
     */
    public function getStepRecipe(): Collection
    {
        return $this->stepRecipe;
    }

    public function addStepRecipe(Recipe $stepRecipe): static
    {
        if (!$this->stepRecipe->contains($stepRecipe)) {
            $this->stepRecipe->add($stepRecipe);
            $stepRecipe->addRecipeStep($this);
        }

        return $this;
    }

    public function removeStepRecipe(Recipe $stepRecipe): static
    {
        if ($this->stepRecipe->removeElement($stepRecipe)) {
            $stepRecipe->removeRecipeStep($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Ingredient>
     */
    public function getStepIngredient(): Collection
    {
        return $this->stepIngredient;
    }

    public function addStepIngredient(Ingredient $stepIngredient): static
    {
        if (!$this->stepIngredient->contains($stepIngredient)) {
            $this->stepIngredient->add($stepIngredient);
            $stepIngredient->addIngredientStep($this);
        }

        return $this;
    }

    public function removeStepIngredient(Ingredient $stepIngredient): static
    {
        if ($this->stepIngredient->removeElement($stepIngredient)) {
            $stepIngredient->removeIngredientStep($this);
        }

        return $this;
    }
}
