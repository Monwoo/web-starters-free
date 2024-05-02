<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsTimeSlotUpload;

/**
 * @extends ServiceEntityRepository<MwsTimeSlotUpload>
 *
 * @method MwsTimeSlotUpload|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsTimeSlotUpload|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsTimeSlotUpload[]    findAll()
 * @method MwsTimeSlotUpload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsTimeSlotUploadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsTimeSlotUpload::class);
    }

//    /**
//     * @return MwsTimeSlotUpload[] Returns an array of MwsTimeSlotUpload objects
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

//    public function findOneBySomeField($value): ?MwsTimeSlotUpload
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
