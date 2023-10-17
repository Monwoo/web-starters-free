<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsOfferStatus;

/**
 * @extends ServiceEntityRepository<MwsOfferStatus>
 *
 * @method MwsOfferStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsOfferStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsOfferStatus[]    findAll()
 * @method MwsOfferStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsOfferStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsOfferStatus::class);
    }

//    /**
//     * @return MwsOfferStatus[] Returns an array of MwsOfferStatus objects
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

//    public function findOneBySomeField($value): ?MwsOfferStatus
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
