<?php

namespace App\Form;

use App\Entity\Recipe;
use App\Entity\Step;
use App\Entity\Ingredient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{TextType, CollectionType, FileType, HiddenType, EntityType, IntegerType};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recipeName', TextType::class)
            ->add('recipePortions', IntegerType::class)
            ->add('recipeSteps', CollectionType::class, [
                'entry_type' => StepType::class, // Formulaire pour chaque étape
                'allow_add' => true, // Permet d'ajouter dynamiquement des étapes
                'allow_delete' => true, // Permet de supprimer une étape
                'by_reference' => false,
                'prototype' => true,
                'label' => false, // Désactiver le label pour la collection
            ])
            ->add('image', FileType::class, [
                'mapped' => false, // Ne sera pas lié à une propriété d'entité
                'required' => false, // Facultatif
            ])
            ->add('selectedIngredients', HiddenType::class, [
                'mapped' => false,  // Le champ n'est pas mappé à une propriété de l'entité
                'attr' => [
                    'id' => 'selected-ingredients',
                    'name' => 'selected-ingredients',
                    'value' => '[]'
                ],  // ID pour manipuler avec JS
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}