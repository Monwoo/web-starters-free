<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsContact;

/**
 * @extends ServiceEntityRepository<MwsContact>
 *
 * @method MwsContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsContact[]    findAll()
 * @method MwsContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsContact::class);
    }

//    /**
//     * @return MwsContact[] Returns an array of MwsContact objects
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

//    public function findOneBySomeField($value): ?MwsContact
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
