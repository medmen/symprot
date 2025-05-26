<?php

namespace App\Controller\Admin;

use App\Entity\Parameter;
use App\Repository\ParameterRepository;
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
        private readonly ParameterRepository $parameterRepository,

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
            ->setDefaultSort(['sort_position' => 'ASC'])
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
        $entityCount = $this->parameterRepository->count([]);

        $moveTop = Action::new('moveTop', false, 'fa fa-arrow-up')
                         ->setHtmlAttributes(['title' => 'Move to top'])
                         ->linkToCrudAction('moveTop')
                         ->displayIf(fn ($entity) => $entity->getSortPosition() > 0);

        $moveUp = Action::new('moveUp', false, 'fa fa-sort-up')
                        ->setHtmlAttributes(['title' => 'Move up'])
                        ->linkToCrudAction('moveUp')
                        ->displayIf(fn ($entity) => $entity->getSortPosition() > 0);

        $moveDown = Action::new('moveDown', false, 'fa fa-sort-down')
                          ->setHtmlAttributes(['title' => 'Move down'])
                          ->linkToCrudAction('moveDown')
                          ->displayIf(fn ($entity) => $entity->getSortPosition() < $entityCount - 1);

        $moveBottom = Action::new('moveBottom', false, 'fa fa-arrow-down')
                            ->setHtmlAttributes(['title' => 'Move to bottom'])
                            ->linkToCrudAction('moveBottom')
                            ->displayIf(fn ($entity) => $entity->getSortPosition() < $entityCount - 1);

        return $actions
            ->add(Crud::PAGE_INDEX, $moveBottom)
            ->add(Crud::PAGE_INDEX, $moveDown)
            ->add(Crud::PAGE_INDEX, $moveUp)
            ->add(Crud::PAGE_INDEX, $moveTop);
    }

    public function moveTop(AdminContext $context): Response
    {
        return $this->move($context, Direction::Top);
    }

    public function moveUp(AdminContext $context): Response
    {
        return $this->move($context, Direction::Up);
    }

    public function moveDown(AdminContext $context): Response
    {
        return $this->move($context, Direction::Down);
    }

    public function moveBottom(AdminContext $context): Response
    {
        return $this->move($context, Direction::Bottom);
    }

    private function move(AdminContext $context, Direction $direction): Response
    {
        $object = $context->getEntity()->getInstance();
        $newPosition = match($direction) {
            Direction::Top => 0,
            Direction::Up => $object->getSortPosition() - 1,
            Direction::Down => $object->getSortPosition() + 1,
            Direction::Bottom => -1,
        };

        $object->setSortPosition($newPosition);
        $this->em->flush();

        $this->addFlash('success', 'The element has been successfully moved.');

        return $this->redirect($context->getRequest()->headers->get('referer'));
    }

}
