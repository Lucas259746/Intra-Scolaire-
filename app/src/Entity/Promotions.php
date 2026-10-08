<?php

namespace App\Entity;

use App\Repository\PromotionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PromotionsRepository::class)]
#[UniqueEntity('name', message: "Cette promotion existe déjà")]
class Promotions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\ManyToOne(inversedBy: 'promotions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $professor = null;

    /**
     * @var Collection<int, PromotionUsers>
     */
    #[ORM\OneToMany(targetEntity: PromotionUsers::class, mappedBy: 'promotion')]
    private Collection $promotionUsers;

    /**
     * @var Collection<int, Projects>
     */
    #[ORM\ManyToMany(targetEntity: Projects::class, mappedBy: 'promotions')]
    private Collection $projects;

    /**
     * @var Collection<int, Grades>
     */
    #[ORM\OneToMany(targetEntity: Grades::class, mappedBy: 'promotion')]
    private Collection $grades;

    /**
     * @var Collection<int, Attendance>
     */
    #[ORM\OneToMany(targetEntity: Attendance::class, mappedBy: 'promotion')]
    private Collection $attendances;



    public function __construct()
    {
        $this->promotionUsers = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->grades = new ArrayCollection();
        $this->attendances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getProfessor(): ?User
    {
        return $this->professor;
    }

    public function setProfessor(?User $professor): static
    {
        $this->professor = $professor;

        return $this;
    }

    /**
     * @return Collection<int, PromotionUsers>
     */
    public function getpromotionUsers(): Collection
    {
        return $this->promotionUsers;
    }

    public function addpromotionUser(promotionUsers $promotionUser): static
    {
        if (!$this->promotionUsers->contains($promotionUser)) {
            $this->promotionUsers->add($promotionUser);
            $promotionUser->setPromotion($this);
        }

        return $this;
    }

    public function removepromotionUser(promotionUsers $promotionUser): static
    {
        if ($this->promotionUsers->removeElement($promotionUser)) {
            // set the owning side to null (unless already changed)
            if ($promotionUser->getPromotion() === $this) {
                $promotionUser->setPromotion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Projects>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    /* public function addProject(Projects $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setPromotion($this);
        }

        return $this;
    }

    public function removeProject(Projects $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getPromotion() === $this) {
                $project->setPromotion(null);
            }
        }

        return $this;
    } */

    public function addProject(Projects $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->addPromotion($this);
        }

        return $this;
    }

    public function removeProject(Projects $project): static
    {
        if ($this->projects->removeElement($project)) {
            $project->removePromotion($this);
        }

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
            $grade->setPromotion($this);
        }

        return $this;
    }

    public function removeGrade(Grades $grade): static
    {
        if ($this->grades->removeElement($grade)) {
            // set the owning side to null (unless already changed)
            if ($grade->getPromotion() === $this) {
                $grade->setPromotion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Attendance>
     */
    public function getAttendances(): Collection
    {
        return $this->attendances;
    }

    /**
     * Retourne la collection des utilisateurs (étudiants) rattachés à la promotion.
     *
     * @return Collection<int, \App\Entity\User>
     */
    public function getStudents(): Collection
    {
        $students = new ArrayCollection();

        foreach ($this->promotionUsers as $promotionUser) {
            $user = $promotionUser->getUser();
            if ($user !== null && !$students->contains($user)) {
                $students->add($user);
            }
        }

        return $students;
    }

    public function addAttendance(Attendance $attendance): static
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances->add($attendance);
            $attendance->setPromotion($this);
        }

        return $this;
    }

    public function removeAttendance(Attendance $attendance): static
    {
        if ($this->attendances->removeElement($attendance)) {
            // set the owning side to null (unless already changed)
            if ($attendance->getPromotion() === $this) {
                $attendance->setPromotion(null);
            }
        }

        return $this;
    }
}
