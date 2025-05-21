<?php

namespace App\Entity;

use App\Repository\StepRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use App\Entity\Recipe;
use App\Entity\StepOperation;
use App\Entity\Operation;
use App\Entity\Ingredient;

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

    #[ORM\ManyToOne(inversedBy: 'recipeSteps')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?Recipe $stepRecipe = null;

    /**
     * @var Collection<int, StepOperation>
     */
    #[ORM\OneToMany(targetEntity: StepOperation::class, mappedBy: 'step', orphanRemoval: true, cascade: ['remove'], fetch: 'LAZY')]
    private Collection $stepOperations;

    public function __construct()
    {
        $this->stepOperations = new ArrayCollection();
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

    
    public function getStepRecipe(): ?Recipe
    {
        return $this->stepRecipe;
    }

    public function setStepRecipe(?Recipe $stepRecipe): static
    {
        $this->stepRecipe = $stepRecipe;

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
            $stepOperation->setStep($this);
        }

        return $this;
    }

    public function removeStepOperation(StepOperation $stepOperation): self
    {
        if ($this->stepOperations->removeElement($stepOperation)) {
            if ($stepOperation->getStep() === $this) {
                $stepOperation->setStep(null);
            }
        }

        return $this;
    }
}
