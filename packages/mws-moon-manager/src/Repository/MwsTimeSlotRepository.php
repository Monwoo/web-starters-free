<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsTimeSlot;
use MWS\MoonManagerBundle\Entity\MwsTimeTag;

/**
 * @extends ServiceEntityRepository<MwsTimeSlot>
 *
 * @method MwsTimeSlot|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsTimeSlot|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsTimeSlot[]    findAll()
 * @method MwsTimeSlot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsTimeSlotRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        // TODO : service injection not working for serializer import ? was buggy data, no pb.... ?
        // protected MwsTimeTagRepository $mwsTimeTagRepository
    ) {
        parent::__construct($registry, MwsTimeSlot::class);
    }

    //    /**
    //     * @return MwsTimeSlot[] Returns an array of MwsTimeSlot objects
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

    //    public function findOneBySomeField($value): ?MwsTimeSlot
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function applyTimingLokup($qb, $timingLookup, $slotName = 's')
    {
        if (!$timingLookup) {
            return;
        }
        [
            'searchKeyword' => $keyword,
            'searchTags' => $searchTags,
            'searchTagsToAvoid' => $searchTagsToAvoid,
        ] = $timingLookup;
        if ($keyword) {
            // TODO : MwsKeyword Data model stuff todo, paid level 2 ocr ?
            // ->setParameter('keyword', '%' . strtolower(str_replace(" ", "", $keyword)) . '%');
        }

        if (count($searchTags)) {
            $orClause = '';
            foreach ($searchTags as $idx => $slug) {
                if ($idx) {
                    $orClause .= ' OR ';
                }
                // $orClause .= "( :tagSlug$idx = tag.slug )";
                // // $orClause .= " AND :tagCategory$idx = tag.categorySlug )";
                // $qb->setParameter("tagSlug$idx", $slug);
                // $qb->setParameter("tagCategory$idx", $category);

                $tagQb = $this->_em->createQueryBuilder()
                ->select("t")
                ->from(MwsTimeTag::class, "t")
                ->setMaxResults(1)
                ->where('t.slug = :slug')
                ->setParameter('slug', $slug);
                $tag = $tagQb->getQuery()->getResult()[0] ?? null;
                // dd($tag);
                // $tag = $this->mwsTimeTagRepository->findOneBy([
                //     'slug' => $slug,
                // ]);
                $orClause .= "( :tag$idx MEMBER OF $slotName.tags )";
                $qb->setParameter("tag$idx", $tag);
            }
            $qb = $qb->andWhere($orClause);
            // dd($qb->getQuery()->getDql());
        }

        if (count($searchTagsToAvoid)) {
            // dd($searchTagsToAvoid);
            foreach ($searchTagsToAvoid as $idx => $slug) {
                $dql = '';
                $tagQb = $this->_em->createQueryBuilder()
                ->select("t")
                ->from(MwsTimeTag::class, "t")
                ->setMaxResults(1)
                ->where('t.slug = :slug')
                ->setParameter('slug', $slug);
                $tag = $tagQb->getQuery()->getResult()[0] ?? null;
                // $tag = $this->mwsTimeTagRepository->findOneBy([
                //     'slug' => $slug,
                // ]);
                $dql .= ":tagToAvoid$idx NOT MEMBER OF $slotName.tags";
                $qb->setParameter("tagToAvoid$idx", $tag);
                // dd($dql);
                $qb = $qb->andWhere($dql);
            }
        }
    }
}
