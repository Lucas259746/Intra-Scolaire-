<?php

namespace App\Entity;

use App\Repository\NotificationRecipientsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRecipientsRepository::class)]
class NotificationRecipients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notificationRecipients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Notifications $notification = null;

    #[ORM\ManyToOne(inversedBy: 'notificationRecipients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $is_read = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $read_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNotificationId(): ?Notifications
    {
        return $this->notification;
    }

    public function setNotificationId(?Notifications $notification): static
    {
        $this->notification = $notification;

        return $this;
    }

    public function getUserId(): ?User
    {
        return $this->user;
    }

    public function setUserId(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->is_read;
    }

    public function setIsRead(bool $is_read): static
    {
        $this->is_read = $is_read;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->read_at;
    }

    public function setReadAt(?\DateTimeImmutable $read_at): static
    {
        $this->read_at = $read_at;

        return $this;
    }
}
