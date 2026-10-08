<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Repository\DocumentsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentsRepository::class)]
class Documents
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Renommé en $user (au lieu de $user_id) et User avec une majuscule
    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(nullable: true, enumType: DocumentType::class)]
    private ?DocumentType $type = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $path = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // Le getter s'appelle maintenant getUser()
    public function getUser(): ?User
    {
        return $this->user;
    }

    // Le setter s'appelle maintenant setUser()
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getType(): ?DocumentType
    {
        return $this->type;
    }

    public function setType(?DocumentType $type): static
    {
        $this->type = $type;
        return $this;
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

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }
}
