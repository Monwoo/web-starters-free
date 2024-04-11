<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<MwsUser>
 *
 * @method MwsUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsUser[]    findAll()
 * @method MwsUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public $teamMemberschoiceLabelHandler = null;
    public $teamMembersQuery = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MwsUser::class);
        $repo = $this;
        $availableRoles = $repo->getAvailableRoles();

        $this->teamMemberschoiceLabelHandler = function ($user)
        use ($availableRoles) { // TODO : remove code duplication betwen user types
            $roles = $user->getRoles();
            return $user->__toString() . ' [ '
            . implode(", ", array_map(function($r) use($availableRoles) {
                return $availableRoles[$r] ?? $r;
            }, array_filter($roles, function($r) {
                return $r !== MwsUser::ROLE_USER;
            }))) . ' ]';
        };
        $this->teamMembersQuery = function ($roles, $targetUser = null)
        use ($repo) : QueryBuilder {
            $qb = $repo->createQueryBuilder('m')
            ->orderBy('m.username', 'ASC');
            // ->addOrderBy('m.email', 'ASC');
            $orRoles = '';
            foreach($roles ?? [] as $idx => $role) {
                $orRoles .= ($idx > 0 ? ' OR ' : '')
                . "m.roles LIKE :role$idx";
                $qb->setParameter("role$idx", "%$role%");
            }
            if (strlen($orRoles)) {
                $qb->AndWhere($orRoles);
            }
            if ($targetUser) {
                $qb->AndWhere('m.id != :selfId');
                $qb->setParameter("selfId", $targetUser->getId());    
            }

            return $qb;
        };

    }

    public function getAvailableRoles() {
        // TODO : better user-role archi ? use frameworks ? or servie
        // => todo sync with src/Form/UpdateUserType.php:38
        return [
            'ROLE_MWS_ADMIN' => 'Administrateur',
            'ROLE_MWS_DIRECTOR' => 'Gérant',
            'ROLE_MWS_COMMERCIAL' => 'Commercial',
            'ROLE_MWS_PROSPECTOR' => 'Prospecteur',
            'ROLE_MWS_SUPPLIER' => 'Fournisseur',
            'ROLE_MWS_CLIENT' => 'Client',
        ];
    }

    public function add(MwsUser $entity, bool $flush = false): void
    {
        $this->getObjectManager()->persist($entity);

        if ($flush) {
            $this->getObjectManager()->flush();
        }
    }

    public function remove(MwsUser $entity, bool $flush = false): void
    {
        $this->getObjectManager()->remove($entity);

        if ($flush) {
            $this->getObjectManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof MwsUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->add($user, true);
    }

//    /**
//     * @return MwsUser[] Returns an array of MwsUser objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MwsUser
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
