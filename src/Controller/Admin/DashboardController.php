<?php

namespace App\Controller\Admin;

use App\Controller\Admin\MovieCrudController;
use App\Entity\Actor;
use App\Entity\Director;
use App\Entity\Genre;
use App\Entity\Movie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        $routeBuilder = $this->get(AdminUrlGenerator::class);

        return $this->redirect($routeBuilder->setController(MovieCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Bimba & Lola Test');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),

            MenuItem::section('Movies'),
            MenuItem::linkToCrud('Genres', 'fa fa-tags', Genre::class),
            MenuItem::linkToCrud('Actors', 'fa fa-user', Actor::class),
            MenuItem::linkToCrud('Directors', 'fa fa-video-camera', Director::class),
            MenuItem::linkToCrud('List of Movies', 'fa fa-film', Movie::class),
        ];
    }
}
