<?php

namespace App\Controller\Admin;

use App\Entity\Geraet;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class GeraetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Geraet::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
