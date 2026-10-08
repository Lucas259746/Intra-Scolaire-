<?php

namespace App\Repository;

use App\Entity\Absences;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Absences>
 */
class AbsencesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Absences::class);
    }

    public function findByStudent(User $student): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :student')
            ->setParameter('student', $student)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->join('u.promotionUsers', 'pu')
            ->join('pu.promotion', 'pr')
            ->where('pr.professor = :teacher')
            ->setParameter('teacher', $teacher)
            ->groupBy('a.id')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isTeacherAllowed(User $teacher, Absences $absence): bool
    {
        return (bool) $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->join('u.promotionUsers', 'pu')
            ->join('pu.promotion', 'pr')
            ->where('a.id = :absence')
            ->andWhere('pr.professor = :teacher')
            ->setParameter('absence', $absence->getId())
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByStudentAndDate(User $student, \DateTime $date): ?Absences
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :student')
            ->andWhere('a.start_date BETWEEN :start AND :end')
            ->setParameter('student', $student)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
