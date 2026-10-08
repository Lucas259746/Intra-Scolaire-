<?php

namespace App\Entity;

use App\Enum\NotificationType;
use App\Repository\NotificationsRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationsRepository::class)]
class Notifications
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(enumType: NotificationType::class)]
    private ?NotificationType $type = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sender = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, NotificationRecipients>
     */
    #[ORM\OneToMany(targetEntity: NotificationRecipients::class, mappedBy: 'notification')]
    private Collection $notificationRecipients;

    public function __construct()
    {
        $this->notificationRecipients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getType(): ?NotificationType
    {
        return $this->type;
    }

    public function setType(NotificationType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, NotificationRecipients>
     */
    public function getNotificationRecipients(): Collection
    {
        return $this->notificationRecipients;
    }

    public function addNotificationRecipient(NotificationRecipients $notificationRecipient): static
    {
        if (!$this->notificationRecipients->contains($notificationRecipient)) {
            $this->notificationRecipients->add($notificationRecipient);
            $notificationRecipient->setNotificationId($this);
        }

        return $this;
    }

    public function removeNotificationRecipient(NotificationRecipients $notificationRecipient): static
    {
        if ($this->notificationRecipients->removeElement($notificationRecipient)) {
            // set the owning side to null (unless already changed)
            if ($notificationRecipient->getNotificationId() === $this) {
                $notificationRecipient->setNotificationId(null);
            }
        }

        return $this;
    }
}
