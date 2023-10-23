<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function findOneWithSlugAndCategory($slug, $categorySlug, $asQueryBuilder = false
    ): MwsOfferStatus|QueryBuilder|null {
        $qb = $this->createQueryBuilder('t')
        ->where('t.slug = :slug')
        ->setParameter('slug', $slug)
        ->setMaxResults(1);
        // dd( $categorySlug);
        if ($categorySlug && strlen($categorySlug)) {
            $qb->andWhere('t.categorySlug = :categorySlug')
            ->setParameter('categorySlug', $categorySlug);
        } else {
            $qb->andWhere("t.categorySlug IS NULL OR t.categorySlug = ''");
        }
    
        return $asQueryBuilder ? $qb : $qb->getQuery()->execute()[0] ?? null;
    }

    public function getTagsByCategorySlugAndSlug() {
        $allTags = $this->findAll();
        $offerTagsByCatSlugAndSlug = array_combine(array_map(function($t) {
            return "{$t->getCategorySlug()}|{$t->getSlug()}";
        }, $allTags), $allTags);

        return $offerTagsByCatSlugAndSlug;
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
