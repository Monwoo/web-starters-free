<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsMessageTchatUpload;

/**
 * @extends ServiceEntityRepository<MwsMessageTchatUpload>
 *
 * @method MwsMessageTchatUpload|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsMessageTchatUpload|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsMessageTchatUpload[]    findAll()
 * @method MwsMessageTchatUpload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsMessageTchatUploadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsMessageTchatUpload::class);
    }

//    /**
//     * @return MwsMessageTchatUpload[] Returns an array of MwsMessageTchatUpload objects
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

//    public function findOneBySomeField($value): ?MwsMessageTchatUpload
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
