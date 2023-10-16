<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsCalendarTracking;

/**
 * @extends ServiceEntityRepository<MwsCalendarTracking>
 *
 * @method MwsCalendarTracking|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsCalendarTracking|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsCalendarTracking[]    findAll()
 * @method MwsCalendarTracking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsCalendarTrackingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsCalendarTracking::class);
    }

//    /**
//     * @return MwsCalendarTracking[] Returns an array of MwsCalendarTracking objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MwsCalendarTracking
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
