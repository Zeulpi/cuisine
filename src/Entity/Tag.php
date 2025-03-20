<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $tagName = null;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\ManyToMany(targetEntity: Recipe::class, mappedBy: 'recipeTags')]
    private Collection $tagRecipe;

    #[ORM\Column(length: 255)]
    private ?string $tagColor = null;

    public function __construct()
    {
        $this->tagRecipe = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTagName(): ?string
    {
        return $this->tagName;
    }

    public function setTagName(string $tagName): static
    {
        $this->tagName = $tagName;

        return $this;
    }

    /**
     * @return Collection<int, Recipe>
     */
    public function getTagRecipe(): Collection
    {
        return $this->tagRecipe;
    }

    public function addTagRecipe(Recipe $tagRecipe): static
    {
        if (!$this->tagRecipe->contains($tagRecipe)) {
            $this->tagRecipe->add($tagRecipe);
            $tagRecipe->addRecipeTag($this);
        }

        return $this;
    }

    public function removeTagRecipe(Recipe $tagRecipe): static
    {
        if ($this->tagRecipe->removeElement($tagRecipe)) {
            $tagRecipe->removeRecipeTag($this);
        }

        return $this;
    }

    public function getTagColor(): ?string
    {
        return $this->tagColor;
    }

    public function setTagColor(string $tagColor): static
    {
        $this->tagColor = $tagColor;

        return $this;
    }
}
