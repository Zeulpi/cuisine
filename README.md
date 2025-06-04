<h1 align="center">Projet perso : App de cuisine</h1>
<h1 align="center">Back Symfony</h1>
https://4galvq.n0c.world/

<br />

Cette app est un projet personnel. Il s'agit d'une app de cuisine pour la préparation et la planification de recettes hebdomadaires. Les features principales sont détaillées plus loin. Ce projet est entièrement réalisé en solo.
Il s'agit de la partie backend de l'app seulement. Le front est réalisé en React, et se trouve dans un autre repo (https://github.com/Zeulpi/cuisine-front)
L'objectif était de réaliser une app selon des critères pré-établis :
<ul>
<li> Back en php, avec symfony
<li> Front en React
<li> Front séparé du back, pour pouvoir plus tard expérimenter sur les parties back ou front selon d'autres technos (Node.js, VueJS, etc ...)
<li> Design et features établis avant le début du développement
<li> Mise en place et utilisation d'un environnement Docker
<li> App responsive (desktop/mobile)
<li> Déploiement de l'app sur une plateforme d'hébergement
</ul>

<br />

## ⚡️ Features

```
Les features du back sont les suivantes. Certaines ont été ajoutées/modifiées au cours du développement (certaines n'étaient pas inclues dans les specs de départ, pour certaines autres j'ai changé d'avis sur la facon de les implémenter, etc ...) :
```
<ul>
<li> Pour le démarrage du projet, mise en place rapide d'une solution EasyAdmin pour peupler les premières données de la base (tags, ingrédients, éléments simples ..)
<li> Mise en place d'un CRUD custom pour les recettes : un template de formulaire commun pour la création/édition de recettes, un controller dédié pour chaque action
<li> Mise en place d'outils d'admin custom pour la gestion des Users (promote/demote des User, tri des User par role, raz des planners, etc ...)
<li> Sécurisation des outils d'admin par roles (ROLE_ADMIN a accés a tous les outils d'admin, ROLE_CREATOR a acces a la creation/edition de recettes, ROLE_USER n'a pas d'acces a l'admin, etc...). D'autres roles peuvent etre ajoutés avec des privileges spécifiques
<li> Mise en place d'une "Gateway" pour l'API front pour centraliser toutes les routes pour l'API -> faciliter le tracking des requetes autorisées (pour l'exercice, je suis parti sur cette méthode plutot que graphQL). Toutes les requetes API sont centralisées sur la gateway et forwardés vers le controller et la methode concernés
<li> Mise en place d'une vérification par captcha pour la création de compte user
<li> Mise en place d'une solution anti BruteForce pour le login d'un User, sous la forme d'un service custom
<li> Toutes les requetes API vérifient la validité du JwToken et la validité du User (un User ne peut pas modifier les données d'un autre User : planner, données perso, etc ..)
<li> Le planning hebdo de chaque User est géré grace a une classe custom Planner (Planner est un objet contenant 4 plannings : la semaine actuelle, la semaine suivantes, et les 2 dernieres semaines passées). Le planning de chaque User est stocké dans un champ de l'entité User plutot qu'une table dédiée
<li> L'inventaire de chaque User est géré avec une table dédiée Fridge. la création d'un User crée son Fridge, la suppression d'un User entraine la suppression du Fridge
</ul>

<br />

# 🚀 Libs externes (& autre) utilisées

<ul>
<li> Google grecaptcha v2
<li> knp-paginator
<li> easyadmin-bundle
<li> jwt-authentication-bundle
<li> nelmio/cors-bundle
<li> Service custom LoginTracker : permet de tracker les tentatives de login d'un User, et d'appliquer un timer incrémental entre les tentatives en fonction des echecs successifs. Je n'ai pas la solution parfaite pour les vols de compte, mais cette solution complique considérablement la tache des scripts de bruteforce.
</ul>

<br />