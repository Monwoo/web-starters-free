<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsTimeQualif;

/**
 * @extends ServiceEntityRepository<MwsTimeQualif>
 *
 * @method MwsTimeQualif|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsTimeQualif|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsTimeQualif[]    findAll()
 * @method MwsTimeQualif[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsTimeQualifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsTimeQualif::class);
    }

//    /**
//     * @return MwsTimeQualif[] Returns an array of MwsTimeQualif objects
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

//    public function findOneBySomeField($value): ?MwsTimeQualif
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
