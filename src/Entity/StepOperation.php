<?php

namespace App\Entity;

use App\Repository\StepOperationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: StepOperationRepository::class)]
class StepOperation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Step::class, inversedBy: 'stepOperations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Step $step = null;

    #[ORM\ManyToOne(targetEntity: Operation::class, inversedBy: 'stepOperations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Operation $operation = null;

    #[ORM\ManyToOne(targetEntity: Ingredient::class, inversedBy: 'stepOperations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Ingredient $ingredient = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private $operationResult = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStep(): ?Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): self
    {
        $this->step = $step;

        return $this;
    }

    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    public function setOperation(?Operation $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    public function setIngredient(?Ingredient $ingredient): self
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    public function getOperationResult(): array
    {
        return $this->operationResult ?? [];
    }

    public function setOperationResult(array $operationResult): self
    {
        $this->operationResult = $operationResult;

        return $this;
    }

    public function addOperationResult(string $result = '', int $operationId): self
    {
        // Ajouter la quantité et l'unité au tableau des quantités de la recette
        $this->operationResult[$operationId] = [
            'result' => $result, // Ajouter le resultat de l'opération
        ];

        return $this;
    }

    public function removeOperationResult(int $operationId): self
    {
        unset($this->operationResult[$operationId]);

        return $this;
    }
}
