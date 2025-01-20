<?php

namespace App\Entity;

use App\Repository\ToolRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ToolRepository::class)]
class Tool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $toolName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $toolImg = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToolName(): ?string
    {
        return $this->toolName;
    }

    public function setToolName(string $toolName): static
    {
        $this->toolName = $toolName;

        return $this;
    }

    public function getToolImg(): ?string
    {
        return $this->toolImg;
    }

    public function setToolImg(?string $toolImg): static
    {
        $this->toolImg = $toolImg;

        return $this;
    }
}
