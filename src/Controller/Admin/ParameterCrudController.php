<?php

namespace App\Controller\Admin;

use App\Entity\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class ParameterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Parameter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['parameter_name', 'geraet'])
            ->setDefaultSort(['parameter_name' => 'DESC']);
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
        yield TextField::new('parameter_name');
        yield BooleanField::new('parameter_selected');
        yield BooleanField::new('parameter_default');

    }

}
