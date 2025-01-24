<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $operationName = null;

    /**
     * @var Collection<int, StepOperation>
     */
    #[ORM\OneToMany(targetEntity: StepOperation::class, mappedBy: 'operation', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $stepOperations;

    public function __construct()
    {
        $this->stepOperations = new ArrayCollection();
    }

      public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperationName(): ?string
    {
        return $this->operationName;
    }

    public function setOperationName(string $operationName): static
    {
        $this->operationName = $operationName;

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
            $stepOperation->setOperation($this);
        }

        return $this;
    }

    public function removeStepOperation(StepOperation $stepOperation): self
    {
        if ($this->stepOperations->removeElement($stepOperation)) {
            if ($stepOperation->getOperation() === $this) {
                $stepOperation->setOperation(null);
            }
        }

        return $this;
    }

}
