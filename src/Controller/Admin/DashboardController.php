<?php

namespace App\Controller\Admin;

use App\Entity\Config;
use App\Entity\Geraet;
use App\Entity\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    #[Route(path: '/admin', name: 'admin')]
    public function index(): Response
    {
        // return parent::index();
        $routeBuilder = $this->adminUrlGenerator;
        $url = $routeBuilder
            ->setController(ParameterCrudController::class)
            ->set('filters[geraet][comparison]', '=')
            ->set('filters[geraet][value]', '1')
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Symprot');
    }

    public function configureMenuItems(): iterable
    {
        // yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
        yield MenuItem::linktoRoute('Back to the website', 'fas fa-home', 'index');
        yield MenuItem::linkToCrud('Geraete', 'fas fa-circle-radiation', Geraet::class);
        yield MenuItem::linkToCrud('Config', 'fas fa-gear', Config::class);
        yield MenuItem::linkToCrud('Parameter', 'fas fa-list-check', Parameter::class)
            ->setQueryParameter('filters[geraet][comparison]', '=')
            ->setQueryParameter('filters[geraet][value]', '1')
        ;
    }
}
