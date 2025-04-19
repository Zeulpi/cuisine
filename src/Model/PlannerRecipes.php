<?php

namespace App\Model;

class PlannerRecipes implements \JsonSerializable
{
    private array $monM;  // Lundi midi
    private array $monE;  // Lundi soir
    private array $tueM;  // Mardi midi
    private array $tueE;  // Mardi soir
    private array $wedM;  // Mercredi midi
    private array $wedE;  // Mercredi soir
    private array $thuM;  // Jeudi midi
    private array $thuE;  // Jeudi soir
    private array $friM;  // Vendredi midi
    private array $friE;  // Vendredi soir
    private array $satM;  // Samedi midi
    private array $satE;  // Samedi soir
    private array $sunM;  // Dimanche midi
    private array $sunE;  // Dimanche soir

    public function __construct()
    {
        $this->monM = [];
        $this->monE = [];
        $this->tueM = [];
        $this->tueE = [];
        $this->wedM = [];
        $this->wedE = [];
        $this->thuM = [];
        $this->thuE = [];
        $this->friM = [];
        $this->friE = [];
        $this->satM = [];
        $this->satE = [];
        $this->sunM = [];
        $this->sunE = [];
    }

    public function jsonSerialize(): array
    {
        return [
            'monM' => $this->monM,
            'monE' => $this->monE,
            'tueM' => $this->tueM,
            'tueE' => $this->tueE,
            'wedM' => $this->wedM,
            'wedE' => $this->wedE,
            'thuM' => $this->thuM,
            'thuE' => $this->thuE,
            'friM' => $this->friM,
            'friE' => $this->friE,
            'satM' => $this->satM,
            'satE' => $this->satE,
            'sunM' => $this->sunM,
            'sunE' => $this->sunE,
        ];
    }
    
    // Méthode pour ajouter une recette à un repas spécifique
    public function addMeal(string $day, int $recipeId, int $portions): void
    {
        $mealKey = $day; // Par exemple 'monM' pour lundi midi

        // On vérifie si le jour est valide
        if (property_exists($this, $mealKey)) {
            $this->{$mealKey}[0] = $recipeId;
            $this->{$mealKey}[1] = $portions;
        } else {
            throw new \Exception('Jour invalide pour un repas');
        }
    }
    public function removeMeal(string $day): void
    {
        $mealKey = $day; // Par exemple 'MonM' pour lundi midi
        // On vérifie si le jour est valide
        if (property_exists($this, $mealKey)) {
            $this->{$mealKey} = [];
        } else {
            throw new \Exception('Jour invalide pour un repas');
        }
    }
}
