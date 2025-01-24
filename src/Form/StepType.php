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
            ->add('stepText', HiddenType::class, [
                'attr' => [
                    'class' => 'step-description-hidden'
                ]
            ])
            ->add('stepTime', IntegerType::class, [
                'attr' => [
                    'min' => 0, 
                    'step' => 1,
                    'type' => 'number',
                    'class' => 'step-time'
                ],
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
                ]
            ])
            ->add('stepSimult', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'step-simult'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Step::class,
        ]);
    }
}