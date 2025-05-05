<?php

namespace App\Controller\Admin;

use App\Entity\Ingredient;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud, KeyValueStore};
use EasyCorp\Bundle\EasyAdminBundle\Field\{IdField, EmailField, TextField, ArrayField, AssociationField, BooleanField, TextEditorField, ChoiceField, ImageField, DateField};

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
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('ingredient_name', 'Nom de l\'ingrédient'),
            ImageField::new('ingredient_img', 'Image de l\'ingrédient')
            ->setUploadDir('public/images/ingredients/') 
            ->setBasePath('images/ingredients/')
            ->setUploadedFileNamePattern('[name]-[uuid].[extension]')
            ->setRequired($pageName === Crud::PAGE_NEW),
        ];
    }
}
