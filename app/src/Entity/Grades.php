<?php

namespace App\Entity;

use App\Enum\GradeStatus;
use App\Repository\GradesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GradesRepository::class)]
class Grades
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'grades')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Projects $project = null;

    #[ORM\ManyToOne(inversedBy: 'grades')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $submission = null;

    #[ORM\Column]
    private ?\DateTime $update_history = null;

    #[ORM\Column(enumType: GradeStatus::class)]
    private ?GradeStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'grades')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Promotions $promotion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Projects
    {
        return $this->project;
    }

    public function setProject(?Projects $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getSubmission(): ?string
    {
        return $this->submission;
    }

    public function setSubmission(?string $submission): static
    {
        $this->submission = $submission;

        return $this;
    }

    public function getUpdateHistory(): ?\DateTime
    {
        return $this->update_history;
    }

    public function setUpdateHistory(\DateTime $update_history): static
    {
        $this->update_history = $update_history;

        return $this;
    }

    public function getStatus(): ?GradeStatus
    {
        return $this->status;
    }

    public function setStatus(GradeStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPromotion(): ?Promotions
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotions $promotion): static
    {
        $this->promotion = $promotion;

        return $this;
    }
}
