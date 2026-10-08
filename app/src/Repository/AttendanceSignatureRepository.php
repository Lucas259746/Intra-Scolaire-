<?php

namespace App\Repository;

use App\Entity\AttendanceSignature;
use App\Entity\Promotions;
use App\Entity\User;
use App\Enum\AttendanceType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AttendanceSignature>
 */
class AttendanceSignatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttendanceSignature::class);
    }

    public function findPendingByPromotionAndDate(Promotions $promotion, \DateTime $date): array
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('s')
            ->where('s.promotion = :promotion')
            ->andWhere('s.date BETWEEN :start AND :end')
            ->andWhere('s.status IN (:pendingStatuses)')
            ->setParameter('promotion', $promotion)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->setParameter('pendingStatuses', [
                AttendanceType::PENDING_PRESENT->value,
                AttendanceType::PENDING_ABSENT->value,
                AttendanceType::PENDING_LATE->value,
            ])
            ->orderBy('s.signedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByStudentAndDate(User $student, \DateTime $date): ?AttendanceSignature
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('s')
            ->andWhere('s.student = :student')
            ->andWhere('s.date BETWEEN :start AND :end')
            ->setParameter('student', $student)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
