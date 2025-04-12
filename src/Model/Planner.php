<?php

namespace App\Model;

use App\Model\PlannerRecipes;

class Planner implements \JsonSerializable
{
    private string $weekStart;
    private string $weekEnd;
    private string $status;
    private PlannerRecipes $recipes;

    public function __construct(string $status = '', string $weekStart = '', string $weekEnd = '')
    {
        $this->status = $status;
        $this->weekStart = $weekStart;
        $this->weekEnd = $weekEnd;
        $this->recipes = new PlannerRecipes();
    }

    // Implémentation de JsonSerializable
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status,
            'weekStart' => $this->weekStart,
            'weekEnd' => $this->weekEnd,
            'recipes' => $this->recipes
        ];
    }

    // Getters et setters pour les propriétés
    public function getWeekStart(): string
    {
        return $this->weekStart;
    }

    public function setWeekStart(string $weekStart): void
    {
        $this->weekStart = $weekStart;
    }

    public function getWeekEnd(): string
    {
        return $this->weekEnd;
    }

    public function setWeekEnd(string $weekEnd): void
    {
        $this->weekEnd = $weekEnd;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function addRecipe(string $day, int $recipeId, int $portions): void
    {
        $this->recipes->addMeal($day, $recipeId, $portions); // On passe la requête à PlannerRecipes
    }
}
