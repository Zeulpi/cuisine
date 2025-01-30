<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use App\Entity\Ingredient;
use App\Entity\Tool;
use App\Entity\Step;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud, KeyValueStore};
use EasyCorp\Bundle\EasyAdminBundle\Field\{IdField, EmailField, TextField, ArrayField, AssociationField, BooleanField, TextEditorField, ChoiceField, CollectionField, ImageField, DateField, IntegerField};

class RecipeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Recipe::class;
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
            TextField::new('recipe_name', 'Nom de la recette'),
            IntegerField::new('recipe_portions', 'Nombre de portions'),
            ImageField::new('recipe_img', 'Image de la recette')
            ->setUploadDir('public/images/recipes/') 
            ->setBasePath('images/recipes/')
            ->setUploadedFileNamePattern('[name]-[uuid].[extension]')
            ->setRequired($pageName === Crud::PAGE_NEW),
        ];
    }
}
