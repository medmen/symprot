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
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
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

    public function configureAssets(Assets $assets): Assets
    {
        // Include external, non-inline assets to comply with CSP (no 'unsafe-inline').
        return $assets
            ->addCssFile('build/ea-move-highlight.css')
            ->addJsFile('build/ea-move-highlight.js');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('geraet'))
            ->add('parameter_selected')
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
    }
    public function configureActions(Actions $actions): Actions
    {
        $moveTop = Action::new('moveTop', false, 'fa fa-arrow-up')
                         ->setHtmlAttributes(['title' => 'Move to top'])
                         ->linkToCrudAction('moveTop')
                         ->displayIf(fn ($entity) => (int) max(0, (int) $entity->getSortPosition()) > 0);

        $moveUp = Action::new('moveUp', false, 'fa fa-sort-up')
                        ->setHtmlAttributes(['title' => 'Move up'])
                        ->linkToCrudAction('moveUp')
                        ->displayIf(fn ($entity) => (int) max(0, (int) $entity->getSortPosition()) > 0);

        $moveDown = Action::new('moveDown', false, 'fa fa-sort-down')
                          ->setHtmlAttributes(['title' => 'Move down'])
                          ->linkToCrudAction('moveDown')
                          ->displayIf(function ($entity) {
                              $countInGroup = $this->parameterRepository->count(['geraet' => $entity->getGeraet()]);
                              $pos = (int) max(0, (int) $entity->getSortPosition());
                              return $pos < $countInGroup - 1;
                          });

        $moveBottom = Action::new('moveBottom', false, 'fa fa-arrow-down')
                            ->setHtmlAttributes(['title' => 'Move to bottom'])
                            ->linkToCrudAction('moveBottom')
                            ->displayIf(function ($entity) {
                                $countInGroup = $this->parameterRepository->count(['geraet' => $entity->getGeraet()]);
                                $pos = (int) max(0, (int) $entity->getSortPosition());
                                return $pos < $countInGroup - 1;
                            });

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
        /** @var \App\Entity\Parameter $object */
        $object = $context->getEntity()->getInstance();
        $geraet = $object->getGeraet();

        // Load all parameters for this Geraet ordered deterministically
        $parameters = $this->parameterRepository->findByGeraetOrdered($geraet);

        // Normalize positions to 0..n-1 to avoid nulls/negatives
        foreach ($parameters as $idx => $param) {
            $param->setSortPosition($idx);
        }

        // Find current index
        $currentIndex = 0;
        foreach ($parameters as $idx => $param) {
            if ($param === $object) {
                $currentIndex = $idx;
                break;
            }
        }

        $targetIndex = match ($direction) {
            Direction::Top => 0,
            Direction::Up => max(0, $currentIndex - 1),
            Direction::Down => min(count($parameters) - 1, $currentIndex + 1),
            Direction::Bottom => max(0, count($parameters) - 1),
        };

        if ($targetIndex !== $currentIndex) {
            // Move the element within the array
            $moved = $parameters[$currentIndex];
            array_splice($parameters, $currentIndex, 1);
            array_splice($parameters, $targetIndex, 0, [$moved]);
        }

        // Reassign contiguous positions
        foreach ($parameters as $idx => $param) {
            $param->setSortPosition($idx);
        }

        $this->em->flush();

        $this->addFlash('success', 'The element has been successfully moved.');

        $referer = (string) $context->getRequest()->headers->get('referer');
        $highlightId = $object->getParameterId();
        if ($referer) {
            // Append or replace the highlight parameter, preserving other query params
            $urlParts = parse_url($referer);
            $query = [];
            if (!empty($urlParts['query'])) {
                parse_str($urlParts['query'], $query);
            }
            $query['highlight'] = $highlightId;
            $newQuery = http_build_query($query);
            $newUrl = ($urlParts['scheme'] ?? '') ? ($urlParts['scheme'] . '://') : '';
            $newUrl .= ($urlParts['host'] ?? '');
            $newUrl .= ($urlParts['port'] ?? '') ? (':' . $urlParts['port']) : '';
            $newUrl .= ($urlParts['path'] ?? '');
            $newUrl .= $newQuery ? ('?' . $newQuery) : '';
            $newUrl .= isset($urlParts['fragment']) ? ('#' . $urlParts['fragment']) : '';
            return $this->redirect($newUrl);
        }

        return $this->redirect($context->getRequest()->getUri());
    }

}
