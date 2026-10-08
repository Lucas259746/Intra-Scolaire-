<?php

namespace App\Repository;

use App\Entity\NotificationRecipients;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationRecipients>
 */
class NotificationRecipientsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationRecipients::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.user = :user')
            ->andWhere('r.is_read = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
