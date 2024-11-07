<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\BookingFormType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Booking;
use Psr\Log\LoggerInterface;
use App\Repository\BookingRepository;
use App\Repository\ServiceRepository;


#[Route('/booking')]
class BookingController extends AbstractController
{

    public function __construct(
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $entityManager,
        private ServiceRepository $serviceRepository,
        private LoggerInterface $logger,
    ) {
        $this->logger = $logger;
    }



    #[Route('/{id<\d+>}', name: 'app_book')]
    public function index(int $id, Request $request): Response
    {

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour réserver un service .');
        }
        // Récupérer le service à partir de l'ID
        $service = $this->serviceRepository->find($id);
        if (!$service) {
            throw $this->createNotFoundException('Le service n\'existe pas');
        }


        $booking = new Booking();
        $booking->setService($service);
        $booking->setUser($this->getUser());

        $form = $this->createForm(BookingFormType::class, $booking);


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedSlot = $request->request->get('slot');
            if (!$selectedSlot) {
                return new JsonResponse(['error' => 'Aucun créneau sélectionné'], Response::HTTP_BAD_REQUEST);
            }
            try {
                $time = \DateTime::createFromFormat('H:i', $selectedSlot);
                if (!$time) {
                    throw new \Exception('Invalid time format');
                }
                $booking->setTime($time);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Format de créneau horaire invalide'], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($booking);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_confirm', ['id' => $booking->getId()]);
        }

        return $this->render('booking/index.html.twig', [
            'controller_name' => 'BookingController',
            'bookingForm' => $form,
            'service' => $service,
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

    #[Route('/get-available-slots', name: 'get_available_slots', methods: ['GET'])]
    public function getAvailableSlots(Request $request, BookingRepository $bookingRepository): JsonResponse
    {

        $serviceId = $request->query->get('service_id');
        $date = $request->query->get('date');
        error_log('Date received: ' . $date);

        if (!$serviceId || !$date) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $service = $this->serviceRepository->find($serviceId);
        if (!$service) {
            return new JsonResponse(['error' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateTime) {
                throw new \Exception('Invalid date format');
            }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }


        $duration = $service->getDuration();
        if (!is_numeric($duration)) {
            return new JsonResponse(['error' => 'Invalid duration'], Response::HTTP_BAD_REQUEST);
        }
        $openingTime = new \DateTime('08:00');
        $closingTime = new \DateTime('18:00');
        $allSlots = [];

        $currentTime = clone $openingTime;
        while ($currentTime < $closingTime) {
            $allSlots[] = $currentTime->format('H:i');
            $currentTime->add(new \DateInterval('PT' . $duration . 'M'));
        }

        $occupiedSlots = $bookingRepository->findAvailableHoraires($service, $dateTime);
        $availableSlots = array_diff($allSlots, $occupiedSlots);

        return new JsonResponse($availableSlots);
    }
}
