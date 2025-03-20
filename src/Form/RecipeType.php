<?php

namespace App\Form;

use App\Entity\Recipe;
use App\Entity\Step;
use App\Entity\Ingredient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{TextType, CollectionType, FileType, HiddenType, IntegerType, ChoiceType};
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Entity\Tag;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recipeName', TextType::class, [
                'label' => 'Nom de la recette',
                'attr' => [
                    'maxlength' => 255,
                    'class' => 'recipe-name-input'
                ],
                'constraints' => [
                    new Length([
                        'max' => 255, // Nombre maximum de caractères
                        'maxMessage' => 'Le nom de la recette ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('recipePortions', IntegerType::class, [
                'label' => 'Pour combien de personnes ?',
                'attr' => [
                    'min' => 1, 
                    'step' => 1,
                    'type' => 'number',
                    'value' => 1,
                ],
            ])
            ->add('recipeTags', EntityType::class, [
                'class' => Tag::class, // Entité liée
                'choice_label' => 'tagName', // Propriété affichée
                'multiple' => true, // Choix multiple
                'expanded' => true, // Affichage sous forme de checkbox
                'by_reference' => false,
                'attr' => ['class' => 'hidden'], // Cacher le champ
            ])
            ->add('recipeSteps', CollectionType::class, [
                'entry_type' => StepType::class, // Formulaire pour chaque étape
                'mapped' => !$options['is_edit_mode'], // ✅ `true` par défaut, `false` en édition
                'allow_add' => true, // Permet d'ajouter dynamiquement des étapes
                'allow_delete' => true, // Permet de supprimer une étape
                'by_reference' => false,
                'prototype' => true,
                'label' => false, // Désactiver le label pour la collection,
                'data' => $options['data']->getRecipeSteps(),
                'entry_options' => [
                    'attr' => ['data-int' => true] // ✅ Indice pour conversion côté formulaire
                ]
            ])
            ->add('image', FileType::class, [
                'mapped' => false, // Ne sera pas lié à une propriété d'entité
                'required' => false, // Facultatif
                'attr' => ['accept' => 'image/*'], // Types de fichiers acceptés
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
            'is_edit_mode' => false,
        ]);
    }
}