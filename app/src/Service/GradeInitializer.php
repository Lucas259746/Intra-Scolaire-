<?php

namespace App\Service;

use App\Entity\Grades;
use App\Entity\Promotions;
use App\Entity\User;
use App\Enum\GradeStatus;
use App\Repository\GradesRepository;
use Doctrine\ORM\EntityManagerInterface;

class GradeInitializer
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GradesRepository $gradesRepository
    ) {}

    public function initializeGradesForStudent(User $student, Promotions $promotion): void
    {
        $projects = $promotion->getProjects();

        foreach ($projects as $project) {
            $existingGrade = $this->gradesRepository->findOneBy([
                'student' => $student,
                'project' => $project,
                'promotion' => $promotion
            ]);

            if (!$existingGrade) {
                $grade = new Grades();
                $grade->setStudent($student);
                $grade->setProject($project);
                $grade->setPromotion($promotion);
                $grade->setStatus(GradeStatus::PENDING);
                $grade->setUpdateHistory(new \DateTime());

                $this->entityManager->persist($grade);
            }
        }

        $this->entityManager->flush();
    }
}
