<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Service;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    //    /**
    //     * @return Booking[] Returns an array of Booking objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Booking
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findAvailableHoraires(Service $service, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b.time')
            ->where('b.service = :service')
            ->andWhere('b.date = :date')  
            ->setParameter('service', $service)
            ->setParameter('date',  $date);
    
        $bookedSlots = $qb->getQuery()->getResult();
    
        $occupiedSlots = [];
        foreach ($bookedSlots as $slot) {
            $occupiedSlots[] = $slot['time']->format('H:i');
        }
    
        return $occupiedSlots;
    }
}
