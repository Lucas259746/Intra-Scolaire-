<?php

namespace App\Repository;

use App\Entity\Projects;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Projects>
 */
class ProjectsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projects::class);
    }

    public function findByStudent(User $student): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.promotions', 'promo')
            ->join('promo.promotionUsers', 'pu')
            ->where('pu.user = :student')
            ->andWhere('p.visibility = true')
            ->setParameter('student', $student)
            ->groupBy('p.id')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.promotions', 'pr')
            ->where('pr.professor = :teacher')
            ->setParameter('teacher', $teacher)
            ->groupBy('p.id')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
