<?php

namespace App\Controller\Admin;

use App\Entity\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

enum Direction
{
    case Top;
    case Up;
    case Down;
    case Bottom;
}


class ParameterCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }
    public static function getEntityFqcn(): string
    {
        return Parameter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['parameter_name', 'geraet'])
            ->setDefaultSort(['position' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('geraet'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        /*
         * return [
         * IdField::new('id'),
         * TextField::new('title'),
         * TextEditorField::new('description'),
         * ];
         */
        yield AssociationField::new('geraet');
        yield TextField::new('parameter_name');
        yield BooleanField::new('parameter_selected');
        yield BooleanField::new('parameter_default');
    }

    public function configureActions(Actions $actions): Actions
    {
        $moveUp = Action::new('moveUp', false, 'fa fa-sort-up')
            ->setHtmlAttributes(['title' => 'Move up'])
            ->linkToCrudAction('moveUp');

        return $actions
            ->add(Crud::PAGE_INDEX, $moveUp);
    }


    public function moveUp(AdminContext $context): Response
    {
        return $this->move($context, Direction::Up);
    }

    private function move(AdminContext $context, Direction $direction): Response
    {
        $object = $context->getEntity()->getInstance();
        $newPosition = match($direction) {
            Direction::Up => $object->getPosition() - 1,
        };

        $object->setPosition($newPosition);
        $this->em->flush();

        $this->addFlash('success', 'The element has been successfully moved.');

        return $this->redirect($context->getRequest()->headers->get('referer'));
    }
}
