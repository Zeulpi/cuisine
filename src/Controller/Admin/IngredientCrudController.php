<?php

namespace App\Controller\Admin;

use App\Entity\Ingredient;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud, KeyValueStore};
use EasyCorp\Bundle\EasyAdminBundle\Field\{IdField, TextField, ImageField, CollectionField};
use Symfony\Component\Form\Extension\Core\Type\{TextType};

class IngredientCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ingredient::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ;
    }

    
    public function configureFields(string $pageName): iterable
    {
        $units = [''];
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('ingredient_name', 'Nom de l\'ingrédient'),
            ImageField::new('ingredient_img', 'Image de l\'ingrédient')
            ->setUploadDir('public/images/ingredients/') 
            ->setBasePath('images/ingredients/')
            ->setUploadedFileNamePattern('[name]-[uuid].[extension]')
            ->setRequired($pageName === Crud::PAGE_NEW),
            TextField::new('ingredientUnitDisplay', 'Unités autorisées')
            ->onlyOnIndex(),
            CollectionField::new('ingredientUnit')
            ->setEntryType(TextType::class)
            ->setFormTypeOption('entry_options', [
                'required' => false,
                'empty_data' => '',
                'trim' => false,
            ])
            ->allowAdd()
            ->allowDelete()
            ->setLabel('Unités autorisées')
            ->onlyOnForms()
        ];
    }
}
