<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByRole(string $role): array
    {
        $users = $this->createQueryBuilder('u')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();

        return array_values(array_filter($users, fn(User $u) => in_array($role, $u->getRoles(), true)));
    }

    public function findNonStudents(): array
    {
        $users = $this->createQueryBuilder('u')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();

        return array_values(array_filter($users, fn(User $u) => !in_array('ROLE_STUDENT', $u->getRoles(), true)));
    }

    public function findStudents(?User $teacher = null, string $search = '', ?int $promotionId = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftjoin('u.promotionUsers', 'pu')
            ->leftjoin('pu.promotion', 'pr')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        if ($teacher !== null) {
            $qb->andWhere('pr.professor = :teacher')
                ->setParameter('teacher', $teacher);
        }

        if ($promotionId !== null) {
            $qb->andWhere('pr.id = :promo')
                ->setParameter('promo', $promotionId);
        }

        if ($search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.firstname)', ':search'),
                    $qb->expr()->like('LOWER(u.lastname)', ':search'),
                    $qb->expr()->like('LOWER(u.email)', ':search')
                )
            )->setParameter('search', '%' . strtolower($search) . '%');
        }

        $users = $qb->groupBy('u.id')->getQuery()->getResult();

        return array_values(array_filter($users, fn(User $u) => in_array('ROLE_STUDENT', $u->getRoles(), true)));
    }
}
