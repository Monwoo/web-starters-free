<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * @return Transaction[]|QueryBuilder Returns an array of Transaction objects
     */
    public function findByBConfigIdWithTotal($bConfigId): array
    {
        $qb = $this->createQueryBuilder('t')
            // https://stackoverflow.com/questions/13970933/doctrine-innerjoin-on-one-to-many-relationship-with-querybuilder
            // ->innerJoin('App\Entity\BillingConfig', 'b', Expr\Join::WITH, 's.id = g.session')
            // ->where('t.billingConfig.id = :bConfigId') => unknown field billingConfig.id error...
            ->where('t.billingConfig = :bConfigId')
            // ->andWhere('t.exampleField = :val')
            ->setParameter('bConfigId', $bConfigId)
            ->orderBy('t.receptionDate', 'ASC');
        // https://stackoverflow.com/questions/50199102/setmaxresults-does-not-works-fine-when-doctrine-query-has-join
        // ->setMaxResults(10)
        // ->getQuery() // Param $shouldReturnQueryBuilder = false will not be possible with augmented total ? Computed rows ?
        // ->getResult(); // Should send the QUERY instead of result to allow quick query extensions ?
        // return $shouldReturnQueryBuilder ? $qb : $qb->getQuery()->getResult();
        $transactions = $qb->getQuery()->getResult();
        $total = new Transaction();
        $total->setLabel("#TOTAL#");

        // TODO : sum transactions in total...

        $transactions[] = $total;
        return $transactions;
    }
    //    /**
    //     * @return Transaction[] Returns an array of Transaction objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transaction
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
