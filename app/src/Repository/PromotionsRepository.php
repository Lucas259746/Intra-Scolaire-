<?php

namespace App\Repository;

use App\Entity\Promotions;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotions>
 */
class PromotionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotions::class);
    }

    public function findByStudent(User $student): array
    {
        return $this->createQueryBuilder('pr')
            ->join('pr.promotionUsers', 'pu')
            ->where('pu.user = :student')
            ->setParameter('student', $student)
            ->orderBy('pr.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.professor = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('pr.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
