<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ServiceRepository;


#[Route('/service')]
class ServiceController extends AbstractController
{

    public function __construct(private ServiceRepository $serviceRepository){}

    #[Route('/show/{id}', name: 'app_show')]
    public function index(int $id): Response
    {   
        $service = $this->serviceRepository->find($id);

        return $this->render('service/index.html.twig', [
            'controller_name' => 'ServiceController',
            'service' => $service
        ]);
    }
}
