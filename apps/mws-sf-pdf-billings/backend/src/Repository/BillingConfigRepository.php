<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Repository;

use App\Entity\BillingConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BillingConfig>
 *
 * @method BillingConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method BillingConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method BillingConfig[]    findAll()
 * @method BillingConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BillingConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillingConfig::class);
    }

    public function save(BillingConfig $entity, bool $flush = false): void
    {
        $this->getObjectManager()->persist($entity);

        if ($flush) {
            $this->getObjectManager()->flush();
        }
    }

    public function remove(BillingConfig $entity, bool $flush = false): void
    {
        $this->getObjectManager()->remove($entity);

        if ($flush) {
            $this->getObjectManager()->flush();
        }
    }

//    /**
//     * @return BillingConfig[] Returns an array of BillingConfig objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BillingConfig
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
