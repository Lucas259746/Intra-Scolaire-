<?php

namespace App\Repository;

use App\Entity\Attendance;
use App\Entity\Promotions;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attendance>
 */
class AttendanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attendance::class);
    }

    //    /**
    //     * @return Attendance[] Returns an array of Attendance objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Attendance
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    // src/Repository/AttendanceRepository.php

    public function findTodayAttendanceByPromotion(Promotions $promotion, \DateTime $date): array
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->where('a.promotion = :promotion')
            ->andWhere('a.date BETWEEN :start AND :end')
            ->setParameter('promotion', $promotion)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->getQuery()
            ->getResult();
    }

    public function findDistinctDatesByPromotion(Promotions $promotion): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.date')
            ->where('a.promotion = :promotion')
            ->setParameter('promotion', $promotion)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByStudentAndDate(User $student, \DateTime $date): ?Attendance
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->andWhere('a.student = :student')
            ->andWhere('a.date BETWEEN :start AND :end')
            ->setParameter('student', $student)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
