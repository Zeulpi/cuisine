<?php

namespace App\Entity;

use App\Repository\RecipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

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
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'UserNote', fetch: 'EAGER')]
    private Collection $recipeNote;

    /**
     * @var Collection<int, Ingredient>
     */
    #[ORM\ManyToMany(targetEntity: Ingredient::class, inversedBy: 'ingredientRecipe')]
    #[Ignore]
    private Collection $recipeIngredient;

    /**
     * @var Collection<int, Step>
     */
    #[ORM\OneToMany(targetEntity: Step::class, mappedBy: 'stepRecipe', orphanRemoval: true, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private Collection $recipeSteps;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $recipePortions = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private $recipeQuantities = [];

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'tagRecipe', fetch: 'EAGER')]
    private Collection $recipeTags;

    public function __construct()
    {
        $this->recipeNote = new ArrayCollection();
        $this->recipeIngredient = new ArrayCollection();
        $this->recipeSteps = new ArrayCollection();
        $this->recipeTags = new ArrayCollection();
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
        }
        $recipeStep->setStepRecipe($this);

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

    public function getRecipePortions(): ?int
    {
        return $this->recipePortions;
    }

    public function setRecipePortions(int $recipePortions): static
    {
        $this->recipePortions = $recipePortions;

        return $this;
    }

    public function getRecipeQuantities(): array
    {
        return $this->recipeQuantities ?? [];
    }

    public function setRecipeQuantities(array $recipeQuantities): self
    {
        $this->recipeQuantities = $recipeQuantities;

        return $this;
    }

    public function addIngredientQuantity(int $ingredientId, float $quantity, string $unit = ''): self
    {
        // Ajouter la quantité et l'unité au tableau des quantités de la recette
        $this->recipeQuantities[$ingredientId] = [
            'quantity' => $quantity,
            'unit' => $unit, // Ajouter l'unité
        ];

        return $this;
    }

    public function removeIngredientQuantity(int $ingredientId): self
    {
        unset($this->recipeQuantities[$ingredientId]);

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getRecipeTags(): Collection
    {
        return $this->recipeTags;
    }

    public function addRecipeTag(Tag $recipeTag): static
    {
        if (!$this->recipeTags->contains($recipeTag)) {
            $this->recipeTags->add($recipeTag);
        }

        return $this;
    }

    public function removeRecipeTag(Tag $recipeTag): static
    {
        $this->recipeTags->removeElement($recipeTag);

        return $this;
    }
}
