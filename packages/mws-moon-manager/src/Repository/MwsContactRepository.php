<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function findOneWithIdAndEmail($username, $email, $asQueryBuilder = false
    ): MwsContact|QueryBuilder|null {
        $qb = $this->createQueryBuilder('m')
        ->where('m.username = :userId')
        ->setParameter('userId', $username)
        ->setMaxResults(1);
        // dd( $categorySlug);
        if ($email && strlen($email)) {
            $qb->andWhere('m.email = :email')
            ->setParameter('email', $email);
        } else {
            $qb->andWhere("m.email IS NULL OR m.email = ''");
        }
    
        return $asQueryBuilder ? $qb : $qb->getQuery()->execute()[0] ?? null;
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
