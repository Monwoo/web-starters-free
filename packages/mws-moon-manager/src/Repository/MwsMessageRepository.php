<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsMessage;

/**
 * @extends ServiceEntityRepository<MwsMessage>
 *
 * @method MwsMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsMessage[]    findAll()
 * @method MwsMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsMessage::class);
    }

//    /**
//     * @return MwsMessage[] Returns an array of MwsMessage objects
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

//    public function findOneBySomeField($value): ?MwsMessage
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
