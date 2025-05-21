<?php

namespace App\Form;

use App\Entity\Step;
use Doctrine\DBAL\Types\BooleanType as TypesBooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{TextareaType, TextType, IntegerType, CheckboxType, ChoiceType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class, [
                        'mapped' => false, // Ne doit pas être modifié par le formulaire
                        'attr' => [
                            'data-int' => true, // Indice pour conversion JS si nécessaire
                        ]
                    ])
            ->add('stepNumber', HiddenType::class, [
                        'attr' => [
                            'data-int' => true, // Indice pour conversion JS si nécessaire
                        ]
                    ])
            ->add('stepText', HiddenType::class, [
                'attr' => [
                    'class' => 'step-description-hidden'
                ]
            ])
            ->add('stepTime', IntegerType::class, [
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                    'type' => 'number',
                    'class' => 'step-time',
                    'data-int' => true,
                ],
                'label' => 'Durée de l\'étape',
                'required' => true,
                'label_attr' => ['class' => 'step-time-label'],
                'constraints' => [
                    new Assert\Positive(),
                ],
            ])
            ->add('stepTimeUnit', ChoiceType::class, [
                'choices' => [
                    'Minutes' => 'minutes',  // L'utilisateur verra 'Minutes' mais la valeur envoyée sera 'minutes'
                    'Secondes' => 'secondes',
                    'Heures' => 'heures',
                ],
                'required' => true,
                'attr' => [
                    'class' => 'step-time-unit'
                ],
                'label' => false,
            ])
            ->add('stepSimult', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'step-simult'
                ],
                'label' => 'Etape simultanée ?',
                'label_attr' => ['class' => 'step-simult-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Step::class,
            'is_edit_mode' => false,
        ]);
    }
}