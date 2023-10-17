<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsOfferTracking;

/**
 * @extends ServiceEntityRepository<MwsOfferTracking>
 *
 * @method MwsOfferTracking|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsOfferTracking|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsOfferTracking[]    findAll()
 * @method MwsOfferTracking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsOfferTrackingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsOfferTracking::class);
    }

//    /**
//     * @return MwsOfferTracking[] Returns an array of MwsOfferTracking objects
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

//    public function findOneBySomeField($value): ?MwsOfferTracking
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
