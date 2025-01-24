<?php

namespace App\Entity;

use App\Repository\RecipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $recipeName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recipeImg = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'UserNote')]
    private Collection $recipeNote;

    /**
     * @var Collection<int, Ingredient>
     */
    #[ORM\ManyToMany(targetEntity: Ingredient::class, inversedBy: 'ingredientRecipe')]
    private Collection $recipeIngredient;

    /**
     * @var Collection<int, Step>
     */
    #[ORM\OneToMany(targetEntity: Step::class, mappedBy: 'stepRecipe', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $recipeSteps;

    public function __construct()
    {
        $this->recipeNote = new ArrayCollection();
        $this->recipeIngredient = new ArrayCollection();
        $this->recipeSteps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipeName(): ?string
    {
        return $this->recipeName;
    }

    public function setRecipeName(string $recipeName): static
    {
        $this->recipeName = $recipeName;

        return $this;
    }

    public function getRecipeImg(): ?string
    {
        return $this->recipeImg;
    }

    public function setRecipeImg(?string $recipeImg): static
    {
        $this->recipeImg = $recipeImg;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getRecipeNote(): Collection
    {
        return $this->recipeNote;
    }

    public function addRecipeNote(User $recipeNote): static
    {
        if (!$this->recipeNote->contains($recipeNote)) {
            $this->recipeNote->add($recipeNote);
        }

        return $this;
    }

    public function removeRecipeNote(User $recipeNote): static
    {
        $this->recipeNote->removeElement($recipeNote);

        return $this;
    }

    /**
     * @return Collection<int, ingredient>
     */
    public function getRecipeIngredient(): Collection
    {
        return $this->recipeIngredient;
    }

    public function addRecipeIngredient(ingredient $recipeIngredient): static
    {
        if (!$this->recipeIngredient->contains($recipeIngredient)) {
            $this->recipeIngredient->add($recipeIngredient);
        }

        return $this;
    }

    public function removeRecipeIngredient(ingredient $recipeIngredient): static
    {
        $this->recipeIngredient->removeElement($recipeIngredient);

        return $this;
    }

    /**
     * @return Collection<int, Step>
     */
    public function getRecipeSteps(): Collection
    {
        return $this->recipeSteps;
    }

    public function addRecipeStep(Step $recipeStep): static
    {
        if (!$this->recipeSteps->contains($recipeStep)) {
            $this->recipeSteps->add($recipeStep);
            $recipeStep->setStepRecipe($this);
        }

        return $this;
    }

    public function removeRecipeStep(Step $recipeStep): static
    {
        if ($this->recipeSteps->removeElement($recipeStep)) {
            // set the owning side to null (unless already changed)
            if ($recipeStep->getStepRecipe() === $this) {
                $recipeStep->setStepRecipe(null);
            }
        }

        return $this;
    }
}
