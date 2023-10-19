<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsContactTracking;

/**
 * @extends ServiceEntityRepository<MwsContactTracking>
 *
 * @method MwsContactTracking|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsContactTracking|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsContactTracking[]    findAll()
 * @method MwsContactTracking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsContactTrackingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsContactTracking::class);
    }

//    /**
//     * @return MwsContactTracking[] Returns an array of MwsContactTracking objects
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

//    public function findOneBySomeField($value): ?MwsContactTracking
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
