<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LoginTracker {
    private $entityManager;
    const MAX_ATTEMPTS = 5; // Maximum de tentatives avant de bloquer une ip
    const TIMER = 5;
    private User $user;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;  // Attribution de l'EntityManager à la propriété
    }

    private function getUser(User $user)
    {
        $this->user = $user;
    }

    public function checkAccess(User $user): array // check le droit d'acces du User selon ses tentatives
    {
        $this->getUser($user);

        $attempts = $this->checkAttempts();
        if (!$attempts[0]) {  // Vérifie si le premier élément est false
            return $attempts; // Retourne directement l'array avec le message d'erreur
        }
        $time = $this->checkTime();
        if (!$time[0]) {  // Vérifie si le premier élément est false
            return $time; // Retourne directement l'array avec le message d'erreur
        }

        // $test = $this->giveBackAttempts();

        return [true, 'ok'];
    }

    private function checkAttempts(): array // Verifier le compteur d'attempts
    {
        // Si failedAttempts est null, on le met à 0. modification ajoutée pour les Users crées avant l'ajout de ce champ
        if ($this->user->getFailedAttempts() === null) {
            $this->user->setFailedAttempts(0);
            $this->entityManager->persist($this->user); // Enregistrer la mise à jour
            $this->entityManager->flush(); // Appliquer les changements en base
        }

        $attempts = $this->user->getFailedAttempts();

        $this->giveBackAttempts();

        if ($attempts >= self::MAX_ATTEMPTS) {
            return [false, 'Max tentatives atteint'];
        }
        return [true, 'ok'];
    }

    private function checkTime()
    {
        $now = new \DateTime();
        $lastAttempt = $this->user->getLastAttempt();
        $multi = $this->user->getFailedAttempts() + 1;
        
        if ($lastAttempt === null) {
            return [true, 'ok'];  // Si c'est la première tentative, on continue sans problème
        }

        $interval = $now->getTimestamp() - $lastAttempt->getTimestamp();
        if ($interval < ((self::TIMER) * $multi)) { // Comparer en secondes
            return [false, 'Délai trop court entre 2 connections !'];
        }

        return [true, 'ok'];
    }

    private function giveBackAttempts() // decrementation auto des attempts en fonction du temps écoulé
    {
        $now = new \DateTime();
        $lastAttempt = $this->user->getLastAttempt();
        $userAttempts = $this->user->getFailedAttempts();
        $multi = $userAttempts + 1;

        if ($lastAttempt === null) {
            return 0;  // Si c'est la première tentative, on ne fait rien
        }
        if ($userAttempts === 0) {
            return 0; // Si le nombre de tentatives est déjà à 0, on ne fait rien
        }
        
        $interval = $now->getTimestamp() - $lastAttempt->getTimestamp(); // Temps écoulé depuis la derniere tentative, en secondes
        $intervalRatio = floor($interval / ((self::TIMER) * $multi * max($userAttempts,1) )); // Combien de fois le temps écoulé dépasse le TIMER User
        $intervalRatio = min($intervalRatio, $this->user->getFailedAttempts()); // Si le Ratio dépasse le nombre de fails, limiter au nombre de fails
        $this->user->decreaseFailedAttempts(1); // Réduire les fails du User d'autant que son Ratio. Le user a attendu assez longtemps, on lui rend X tentatives

        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
        return $intervalRatio;
    }

    public function failedAttempt() // Appeler si La tentative de login a échoué
    {
        $userAttempts = $this->user->getFailedAttempts();
        // Si failedAttempts est null, le mettre à 0 (au cas où ce n'est pas déjà fait)
        if ($this->user->getFailedAttempts() === null) {
            $this->user->setFailedAttempts(0);
        }
        $this->user->setLastAttempt((new \DateTime()));
        $userAttempts++;
        $this->user->setFailedAttempts($userAttempts);
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        // return $userAttempts;
    }
    public function successAttempt() // Appeler si le login est reussi, on remet les tries a 0
    {
        $this->user->setLastAttempt((new \DateTime()));
        $this->user->resetFailedAttempts();
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

}