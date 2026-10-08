<?php

namespace App\Repository;

use App\Entity\Grades;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Grades>
 */
class GradesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grades::class);
    }

    public function findByStudent(User $student): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.student = :student')
            ->setParameter('student', $student)
            ->orderBy('g.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.student', 'u')
            ->join('u.promotionUsers', 'pu')
            ->join('pu.promotion', 'pr')
            ->where('pr.professor = :teacher')
            ->setParameter('teacher', $teacher)
            ->groupBy('g.id')
            ->orderBy('g.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSubmittedByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.student', 'u')
            ->join('u.promotionUsers', 'pu')
            ->join('pu.promotion', 'pr')
            ->where('pr.professor = :teacher')
            ->andWhere('g.status = :status')
            ->setParameter('teacher', $teacher)
            ->setParameter('status', \App\Enum\GradeStatus::SUBMITTED)
            ->groupBy('g.id')
            ->orderBy('g.update_history', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function isTeacherAllowed(User $teacher, Grades $grade): bool
    {
        return (bool) $this->createQueryBuilder('g')
            ->join('g.student', 'u')
            ->join('u.promotionUsers', 'pu')
            ->join('pu.promotion', 'pr')
            ->where('g.id = :grade')
            ->andWhere('pr.professor = :teacher')
            ->setParameter('grade', $grade->getId())
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
