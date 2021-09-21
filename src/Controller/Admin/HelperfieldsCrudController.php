<?php

namespace App\Controller\Admin;

use App\Entity\Helperfields;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class HelperfieldsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Helperfields::class;
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
