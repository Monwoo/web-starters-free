<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsTimeSlot;
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
    public function __construct(
        ManagerRegistry $registry,
        protected MwsTimeSlotRepository $mwsTimeSlotRepository,
    ) {
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

        $qb->orderBy('LOWER(t.slug)');
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

    public function findAllMaxPathsIdxBySlug($timingLookup = null): ?array
    {
        $fromClass = MwsTimeSlot::class;
        $qb = $this->_em->createQueryBuilder('s');
        // $qb->select("DISTINCT s.maxPath");
        $qb->from($fromClass, 's');
        $qb->AddSelect("t.slug");
        $qb->AddSelect("GROUP_CONCAT(s.maxPath, '#_;_#') as MaxPaths");

        $qb = $qb->leftJoin('s.maxPriceTag', 't');
        $qb->where("s MEMBER OF t.mwsTimeSlots");
        $this->mwsTimeSlotRepository->applyTimingLokup($qb, $timingLookup);

        $qb->groupBy('t.slug');
        $qb->orderBy('t.slug');
        $tagsGrouped = $qb->getQuery()->getResult();
        // dd($tagsGrouped);

        // $allMaxPathsBySlug[$tagSlug]['rules'][$ruleIndex]
        // $allMaxPathsBySlug[$tagSlug]['haveRawPice']
        $maxPathsBySlug = [];
        foreach($tagsGrouped as ['slug'=>$slug, 'MaxPaths' => $maxPaths]) {
            $maxPathsBySlug[$slug] = [
                'rules' => [],
                'haveRawPice' => false,
            ];
            $maxPaths = explode('#_;_#', $maxPaths);
            foreach ($maxPaths as $path) {
                $path = json_decode($path, true);
                $ruleIdx = $path['maxRuleIndex'];
                $maxPathsBySlug[$slug]['rules'][$ruleIdx] = true;
                $maxPathsBySlug[$slug]['haveRawPice']
                = 0 == $path['maxLimitPriority'];
            }
        }
        return $maxPathsBySlug;
    }


    private function findAllMaxPathsIdxBySlugLegacy($timingLookup = null): ?array
    {
        $qb = $this->createQueryBuilder('t');
        // TODO : optim :
        // SELECT a, GROUP_CONCAT(b)
        // FROM (SELECT DISTINCT a, b
        // FROM MyTable)
        // GROUP BY a
        // https://stackoverflow.com/questions/18285713/how-to-avoid-duplication-in-group-concat
        // https://sqlite.org/forum/info/221c2926f5e6f155
        // OK : GROUP_CONCAT(DISTINCT s.maxPath) as maxPaths
        // KO : GROUP_CONCAT(DISTINCT s.maxPath, '#_;_#') as maxPaths

        $qb = $qb->select("
            t.slug, GROUP_CONCAT(s.maxPath) as maxPaths
        ");

        // $qb = $qb->leftJoin('t.mwsTimeSlots', 's');
        // $qb = $qb->leftJoin('(SELECT DISTINCT maxPath FROM t.mwsTimeSlots)', 's');
        $fromClass = MwsTimeSlot::class;
        // $qb->addSelect("(SELECT at.addresstypeName
        //     FROM $fromClass at
        //     WHERE at.addresstypeId = a.addresstypeId) AS s"
        // ) https://stackoverflow.com/questions/18162841/doctrine-2-how-do-you-use-a-subquery-column-in-the-select-clause
        // $qb->addSelect("(SELECT DISTINCT s.maxPath
        //     FROM $fromClass s
        //     WHERE s MEMBER OF t.mwsTimeSlots) AS maxPath"
        // );

        $subQb = $this->_em->createQueryBuilder();
        $subQb->select("DISTINCT s.maxPath FROM $fromClass s");
        $subQb->where("s MEMBER OF t.mwsTimeSlots");
        $this->mwsTimeSlotRepository->applyTimingLokup($subQb, $timingLookup);
        // dd($subQb->getQuery()->getDql());
        $qb->addSelect(
            "({$subQb->getQuery()->getDql()}) AS maxPath"
        );
        // Sub filters param going up to parent :
        $qb->setParameters($subQb->getParameters());

        // $qb->from('(SELECT DISTINCT a, b FROM ' . MwsTimeSlot::class . ')');
        // $qb->addGroupBy("t.slug");
        // $qb->addGroupBy("s.maxPath");
        $qb->orderBy('t.slug');
        $tagsGrouped = $qb->getQuery()->getResult();

        // $allMaxPathsBySlug[$tagSlug]['rules'][$ruleIndex]
        // $allMaxPathsBySlug[$tagSlug]['haveRawPice']
        dd($tagsGrouped);

        $tags = array_map(function ($g) {
            return $g['self'];
            // return $g[0];
        }, $tagsGrouped);
        return [$tags, $tagsGrouped];
    }
}
