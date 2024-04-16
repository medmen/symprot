<?php

namespace App\Controller\Admin;

use App\Entity\Geraet;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class GeraetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Geraet::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('geraet_id'),
            TextField::new('geraet_name'),
            TextEditorField::new('geraet_beschreibung'),
        ];
    }
}
