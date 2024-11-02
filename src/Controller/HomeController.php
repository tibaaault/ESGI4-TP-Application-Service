<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ServiceRepository;

class HomeController extends AbstractController
{   
    public function __construct(private ServiceRepository $serviceRepository)
    { }


    #[Route('/', name: 'app_home')]
    public function index(): Response
    {   
        $services = $this->serviceRepository->findAll();

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'services' => $services
        ]);
    }
}
