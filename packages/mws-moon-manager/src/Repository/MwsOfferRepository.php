<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsOffer;
use MWS\MoonManagerBundle\Entity\MwsOfferStatus;

/**
 * @extends ServiceEntityRepository<MwsOffer>
 *
 * @method MwsOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsOffer[]    findAll()
 * @method MwsOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsOffer::class);
    }

    // TODO doc : should use this function.... instead of entity->addTag....
    public function addTag($offer, $tag) {
        // TODO or TIPS ? : do in some Doctrine postpersiste listener ? well, no event on addTag right ?
        $em = $this->getEntityManager();
        /** @var MwsOfferStatusRepository */
        $tagsRepo =  $em->getRepository(MwsOfferStatus::class);
        if ($tag->getCategorySlug()) {
            $category = $tagsRepo->findOneWithSlugAndCategory(
                $tag->getCategorySlug(), null // TIPS : OK since only ONE LEVEL of category
            );
            if ($category && !$category->isCategoryOkWithMultiplesTags()) {
                // dd($offer->getTags());
                // Avoid tags duplicate if category avoid multiples, clean up first
                $tagsToRemove = [];
                foreach ($offer->getTags() as $existingTag) {
                    if ($tag->getCategorySlug()
                    === $existingTag->getCategorySlug()) {
                        $tagsToRemove[] = $existingTag;
                    }
                }
                foreach ($tagsToRemove as $t) {
                    // $offer->getTags()->remove($id);
                    $offer->removeTag($t);
                }
            }
        }
        $offer->addTag($tag);
    }

    // TODO : doc : will return ALL category and sub category tags
    public function getExtendedTags($offer) {
        $extendedTags = [];
        $tags = $offer->getTags();

        $em = $this->getEntityManager();
        /** @var MwsOfferStatusRepository */
        $tagsRepo =  $em->getRepository(MwsOfferStatus::class);

        foreach ($tags as $tag) {
            $extendedTags[] = $tag;
            if ($tag->isCategoryOkWithMultiplesTags()) {
                // TODO : more like a repository function, need to seek tags...
                $extendedTags[] = $tagsRepo->findBy([
                    'categorySlug' => $tag->getCategorySlug()
                ]);
            }
        }
    }
    
//    /**
//     * @return MwsOffer[] Returns an array of MwsOffer objects
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

//    public function findOneBySomeField($value): ?MwsOffer
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
