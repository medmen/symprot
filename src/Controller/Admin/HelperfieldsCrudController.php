<?php

namespace App\Controller\Admin;

use App\Entity\Helperfields;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class HelperfieldsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Helperfields::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
           ->setSearchFields(['helperfield_name', 'helperfield_label'])
           ->setDefaultSort(['helperfield_name' => 'DESC']);
       ;
   }
   public function configureFilters(Filters $filters): Filters
   {
        return $filters
            ->add(EntityFilter::new('geraet'))
            ;

   }

    public function configureFields(string $pageName): iterable
    {
        /**
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
         */
        yield AssociationField::new('geraet');
        yield TextField::new('helperfield_name');
        yield TextField::new('helperfield_label');
        yield TextField::new('helperfield_help');
        yield TextField::new('helperfield_placeholder');
    }
}
