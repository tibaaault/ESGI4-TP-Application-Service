<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create();

       $users = [];
       for ($i = 1; $i <= 10; $i++) {
           $user = new User();
           $user->setEmail("user$i@example.com");
           $user->setRoles(['ROLE_USER']);

           $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
           $user->setPassword($hashedPassword);
           $users[] = $user;
           $manager->persist($user);
       }


       $services = [];
       for ($i = 1; $i <= 20; $i++) {
           $service = new Service();
           $service->setType($faker->randomElement(['Cleaning', 'Repair', 'Consulting', 'Delivery']));
           $service->setTitle($faker->sentence(3));
           $service->setDescription($faker->paragraph());
           $service->setCity($faker->city());
           $service->setAdress($faker->streetAddress());
           $service->setCode(intval($faker->postcode()));
           $service->setDuration($faker->numberBetween(30, 240)); // Durée en minutes
           $service->setPricce($faker->numberBetween(50, 500)); // Prix en monnaie locale
           $service->setImg($faker->optional()->imageUrl(640, 480, 'business', true, 'Faker')); // Image facultative

           // Sauvegarde du service dans un tableau pour référence
           $services[] = $service;
           $manager->persist($service);
       }

       // Création des bookings
       for ($i = 1; $i <= 50; $i++) {
           $booking = new Booking();

           // Date et heure de réservation
           $booking->setDate($faker->dateTimeBetween('-1 year', 'now'));
           $booking->setTime($faker->dateTimeBetween('08:00', '18:00'));

           // Attribution aléatoire d'un utilisateur et d'un service
           $booking->setUser($faker->randomElement($users));
           $booking->setService($faker->randomElement($services));

           $manager->persist($booking);
       }

       $manager->flush();
   }
}