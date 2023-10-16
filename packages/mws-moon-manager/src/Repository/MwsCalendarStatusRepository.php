<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsCalendarStatus;

/**
 * @extends ServiceEntityRepository<MwsCalendarStatus>
 *
 * @method MwsCalendarStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsCalendarStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsCalendarStatus[]    findAll()
 * @method MwsCalendarStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsCalendarStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsCalendarStatus::class);
    }

//    /**
//     * @return MwsCalendarStatus[] Returns an array of MwsCalendarStatus objects
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

//    public function findOneBySomeField($value): ?MwsCalendarStatus
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
