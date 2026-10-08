<?php

namespace App\Repository;

use App\Entity\PromotionUsers;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromotionUsers>
 */
class PromotionUsersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, promotionUsers::class);
    }

    public function findByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('pu')
            ->join('pu.promotion', 'pr')
            ->where('pr.professor = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('pu.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStudent(User $student): array
    {
        return $this->createQueryBuilder('pu')
            ->where('pu.user = :student')
            ->setParameter('student', $student)
            ->orderBy('pu.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
