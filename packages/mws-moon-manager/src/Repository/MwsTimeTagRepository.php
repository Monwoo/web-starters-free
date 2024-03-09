<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsTimeTag;

/**
 * @extends ServiceEntityRepository<MwsTimeTag>
 *
 * @method MwsTimeTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsTimeTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsTimeTag[]    findAll()
 * @method MwsTimeTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsTimeTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsTimeTag::class);
    }

    //    /**
    //     * @return MwsTimeTag[] Returns an array of MwsTimeTag objects
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

    public function findAllTagsWithCounts(): ?array
    {
        $qb = $this->createQueryBuilder('t');

        // $qb->orderBy("t.sourceTimeGMT", "ASC");
        // https://stackoverflow.com/questions/45756622/doctrine-query-with-nullable-optional-join
        $qb = $qb->leftJoin('t.mwsTimeSlots', 's');
        $qb = $qb->leftJoin('t.mwsTimeTags', 'c');
        $qb = $qb->leftJoin('t.mwsTimeQualifs', 'q');

        // https://www.php.net/manual/fr/function.strftime.php
        // count(a.mwsTimeTags) as categoriesCount,
        // count(t.mwsTimeSlots) as tSlotCount,
        // https://stackoverflow.com/questions/3679777/how-to-count-one-to-many-relationships
        // count(s.id) as tSlotCount,
        // count(c.id) as categoriesCount,
        // count(q.id) as tQualifCount
        // $qb->expr()->countDistinct('c.id')
        $qb = $qb->select("
            t as self,
            COUNT(DISTINCT c.id) as categoriesCount,
            COUNT(DISTINCT s.id) as tSlotCount,
            COUNT(DISTINCT q.id) as tQualifCount
        ");

        $qb->addGroupBy("t.id");

        // paginator ? to allow re orders ? may be too much, get param ? :
        // + multi sort ?
        // $timings = $paginator->paginate(
        //     $query, /* query NOT result */
        //     $request->query->getInt('page', 1), /*page number*/
        //     // $request->query->getInt('pageLimit', 448), /*page limit, 28*16 */
        //     $request->query->getInt('pageLimit', 124), /*page limit */
        // );

        $qb->orderBy('t.slug');
        // $qb->orderBy('tQualifCount', 'DESC');
        // $qb->addOrderBy('tSlotCount');
        // $qb->addOrderBy('categoriesCount');

        $tagsGrouped = $qb->getQuery()->getResult();
        // dd($tagsGrouped );
        $tags = array_map(function ($g) {
            return $g['self'];
            // return $g[0];
        }, $tagsGrouped);
        return [$tags, $tagsGrouped];
    }
}
