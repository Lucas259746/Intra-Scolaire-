<?php

namespace App\Entity;

use App\Repository\ProjectsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectsRepository::class)]
class Projects
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column]
    private bool $visibility = False;

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $google_drive = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $due_date = null;

    /**
     * @var Collection<int, Grades>
     */
    #[ORM\OneToMany(targetEntity: Grades::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $grades;

    /**
     * @var Collection<int, promotions>
     */
    #[ORM\ManyToMany(targetEntity: Promotions::class, inversedBy: 'projects')]
    private Collection $promotions;

    public function __construct()
    {
        $this->grades = new ArrayCollection();
        $this->promotions = new ArrayCollection();
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

    public function isVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getGoogleDrive(): ?string
    {
        return $this->google_drive;
    }

    public function setGoogleDrive(?string $google_drive): static
    {
        $this->google_drive = $google_drive;

        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->due_date;
    }

    public function setDueDate(?\DateTime $due_date): static
    {
        $this->due_date = $due_date;

        return $this;
    }

    /**
     * @return Collection<int, Grades>
     */
    public function getGrades(): Collection
    {
        return $this->grades;
    }

    public function addGrade(Grades $grade): static
    {
        if (!$this->grades->contains($grade)) {
            $this->grades->add($grade);
            $grade->setProject($this);
        }

        return $this;
    }

    public function removeGrade(Grades $grade): static
    {
        if ($this->grades->removeElement($grade)) {
            // set the owning side to null (unless already changed)
            if ($grade->getProject() === $this) {
                $grade->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Promotions>
     */
    public function getPromotions(): Collection
    {
        return $this->promotions;
    }

    public function addPromotion(Promotions $promotion): static
    {
        if (!$this->promotions->contains($promotion)) {
            $this->promotions->add($promotion);
        }

        return $this;
    }

    public function removePromotion(Promotions $promotion): static
    {
        $this->promotions->removeElement($promotion);

        return $this;
    }
}
