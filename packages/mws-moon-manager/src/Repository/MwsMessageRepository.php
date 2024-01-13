<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function getMessagesByProjectIdFromOffers($offers, $asQueryBuilder = false)
    {
        $offersProjectIds = array_map(function ($offer) {
            // TODO : what if null ?
            // return $offer->getProjectId();
            $projectId = implode(
                '',
                array_slice(
                    explode('-', $offer->getSlug()),
                    -1
                )
            );
            return $projectId;
        }, $offers);
        // dd($offersProjectIds);
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.projectId IN (:offersProjectIds)')
            // ->groupBy('m.projectId')
            // ->addGroupBy('m.projectId')
            ->setParameter('offersProjectIds', $offersProjectIds);

        // dd($qb->getQuery()->execute());
        // TODO : no way for query builder to return associative array ?
        // rename function or do not allow query builder return ? 
        return $asQueryBuilder ? $qb : array_reduce(
            $qb->getQuery()->execute(),
            function ($acc, $m) {
                if (!array_key_exists($m->getProjectId(), $acc)) {
                    $acc[$m->getProjectId()] = [];
                }
                $acc[$m->getProjectId()][] = $m;
                return $acc;
            },
            []
         ) ?? null;
    }
}
