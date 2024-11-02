<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\BookingFormType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\ServiceRepository;


#[Route('/booking')]
class BookingController extends AbstractController
{

    public function __construct(
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $entityManager,
        private ServiceRepository $serviceRepository,
    ) {}

    #[Route('/{id<\d+>}', name: 'app_book')]
    public function index(int $id, Request $request): Response
    {

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to book a service.');
        }
        // Récupérer le service à partir de l'ID
        $service = $this->serviceRepository->find($id);
        if (!$service) {
            throw $this->createNotFoundException('Service not found');
        }

        $bookedSlots = $this->bookingRepository->findBy(['service' => $service]);

        $reservedTimes = [];
        foreach ($bookedSlots as $booking) {
            $reservedTimes[] = $booking->getTime()->format('H:i'); 
        }

        $booking = new Booking();
        $form = $this->createForm(BookingFormType::class, $booking);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $booking->setService($service);
            $booking->setUser($this->getUser());
            $this->entityManager->persist($booking);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_confirm', ['id' => $booking->getId()]);
        }

        return $this->render('booking/index.html.twig', [
            'controller_name' => 'BookingController',
            'bookingForm' => $form,
            'service' => $service,
            'reservedTimes' => $reservedTimes,
        ]);
    }

    #[Route('/confirm/{id}', name: 'app_confirm')]
    public function confirmBooking(int $id): Response
    {
        $booking = $this->bookingRepository->find($id);
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        if ($booking->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to access this booking');
        }

        $service = $booking->getService();
        $booking = $this->bookingRepository->find($id);

        $this->addFlash('success', 'Validation de votre réservation.');

        return $this->render('booking/confirm.html.twig', [
            'controller_name' => 'BookingController',
            'booking' => $booking,
            'service' => $service
        ]);
    }

    #[Route('/user', name: 'app_user_bookings')]
    public function userBooking(): Response
    {
        $bookings = $this->bookingRepository->findBy(['user' => $this->getUser()]);


        return $this->render('booking/bookings.html.twig', [
            'controller_name' => 'BookingController',
            'bookings' => $bookings,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_delete')]
    public function deleteBooking(int $id): Response
    {

        $booking = $this->bookingRepository->find($id);
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }
        $this->entityManager->remove($booking);
        $this->entityManager->flush();

        // Ajoutez un message flash ici
        $this->addFlash('success', 'Réservation supprimée avec succès.');

        return $this->redirectToRoute('app_user_bookings');
    }
}
