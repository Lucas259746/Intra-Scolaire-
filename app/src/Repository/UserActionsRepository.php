<?php

namespace App\Repository;

use App\Entity\UserActions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserActions>
 */
class UserActionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActions::class);
    }

    public function deleteOlderThan(\DateTimeImmutable $before): int
    {
        return $this->createQueryBuilder('u')
            ->delete()
            ->where('u.created_at < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }
}
