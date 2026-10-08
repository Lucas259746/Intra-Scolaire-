<?php

namespace App\Entity;

use App\Entity\Documents;
use App\Entity\Promotions;
use App\Entity\User;
use App\Enum\AttendanceType;
use App\Repository\AttendanceSignatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttendanceSignatureRepository::class)]
class AttendanceSignature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Promotions $promotion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(enumType: AttendanceType::class)]
    private ?AttendanceType $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $signedAt = null;

    #[ORM\ManyToOne]
    private ?Documents $document = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPromotion(): ?Promotions
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotions $promotion): static
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStatus(): ?AttendanceType
    {
        return $this->status;
    }

    public function setStatus(AttendanceType $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getSignedAt(): ?\DateTimeInterface
    {
        return $this->signedAt;
    }

    public function setSignedAt(?\DateTimeInterface $signedAt): static
    {
        $this->signedAt = $signedAt;

        return $this;
    }
    public function setDocument(?Documents $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getDocument(): ?Documents
    {
        return $this->document;
    }
}
