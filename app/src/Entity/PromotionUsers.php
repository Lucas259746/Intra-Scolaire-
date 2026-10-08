<?php

namespace App\Entity;

use App\Repository\PromotionUsersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromotionUsersRepository::class)]
class PromotionUsers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'promotionUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'promotionUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Promotions $promotion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
