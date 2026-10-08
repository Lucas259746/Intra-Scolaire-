<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\Table(name: '"user"')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone_number = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $github = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $google_drive = null;

    /**
     * @var Collection<int, Promotions>
     */
    #[ORM\OneToMany(targetEntity: Promotions::class, mappedBy: 'professor')]
    private Collection $promotions;

    /**
     * @var Collection<int, PromotionUsers>
     */
    #[ORM\OneToMany(targetEntity: PromotionUsers::class, mappedBy: 'user')]
    private Collection $promotionUsers;

    /**
     * @var Collection<int, Documents>
     */
    #[ORM\OneToMany(targetEntity: Documents::class, mappedBy: 'user')]
    private Collection $documents;

    /**
     * @var Collection<int, Absences>
     */
    #[ORM\OneToMany(targetEntity: Absences::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $absences;

    /**
     * @var Collection<int, Grades>
     */
    #[ORM\OneToMany(targetEntity: Grades::class, mappedBy: 'student', orphanRemoval: true)]
    private Collection $grades;

    /**
     * @var Collection<int, NotificationRecipients>
     */
    #[ORM\OneToMany(targetEntity: NotificationRecipients::class, mappedBy: 'user')]
    private Collection $notificationRecipients;

    /**
     * @var Collection<int, UserActions>
     */
    #[ORM\OneToMany(targetEntity: UserActions::class, mappedBy: 'user')]
    private Collection $userActions;

    /**
     * @var Collection<int, Attendance>
     */
    #[ORM\OneToMany(targetEntity: Attendance::class, mappedBy: 'student')]
    private Collection $attendances;

    public function __construct()
    {
        $this->promotions = new ArrayCollection();
        $this->promotionUsers = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->grades = new ArrayCollection();
        $this->notificationRecipients = new ArrayCollection();
        $this->userActions = new ArrayCollection();
        $this->attendances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstname, $this->lastname);
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getGithub(): ?string
    {
        return $this->github;
    }

    public function setGithub(?string $github): static
    {
        $this->github = $github;

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
            $promotion->setProfessor($this);
        }

        return $this;
    }

    public function removePromotion(Promotions $promotion): static
    {
        if ($this->promotions->removeElement($promotion)) {
            // set the owning side to null (unless already changed)
            if ($promotion->getProfessor() === $this) {
                $promotion->setProfessor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PromotionUsers>
     */
    public function getPromotionUsers(): Collection
    {
        return $this->promotionUsers;
    }

    public function addPromotionUser(PromotionUsers $promotionUser): static
    {
        if (!$this->promotionUsers->contains($promotionUser)) {
            $this->promotionUsers->add($promotionUser);
            $promotionUser->setUser($this);
        }

        return $this;
    }

    public function removePromotionUser(PromotionUsers $promotionUser): static
    {
        if ($this->promotionUsers->removeElement($promotionUser)) {
            // set the owning side to null (unless already changed)
            if ($promotionUser->getUser() === $this) {
                $promotionUser->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Documents>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Documents $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setUser($this);
        }

        return $this;
    }

    public function removeDocument(Documents $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getUser() === $this) {
                $document->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Absences>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(Absences $absence): static
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setUser($this);
        }

        return $this;
    }

    public function removeAbsence(Absences $absence): static
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getUser() === $this) {
                $absence->setUser(null);
            }
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
            $grade->setStudentId($this);
        }

        return $this;
    }

    public function removeGrade(Grades $grade): static
    {
        if ($this->grades->removeElement($grade)) {
            // set the owning side to null (unless already changed)
            if ($grade->getStudentId() === $this) {
                $grade->setStudentId(null);
            }
        }

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
            $notificationRecipient->setUserId($this);
        }

        return $this;
    }

    public function removeNotificationRecipient(NotificationRecipients $notificationRecipient): static
    {
        if ($this->notificationRecipients->removeElement($notificationRecipient)) {
            // set the owning side to null (unless already changed)
            if ($notificationRecipient->getUserId() === $this) {
                $notificationRecipient->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserActions>
     */
    public function getUserActions(): Collection
    {
        return $this->userActions;
    }

    public function addUserAction(UserActions $userAction): static
    {
        if (!$this->userActions->contains($userAction)) {
            $this->userActions->add($userAction);
            $userAction->setUserId($this);
        }

        return $this;
    }

    public function removeUserAction(UserActions $userAction): static
    {
        if ($this->userActions->removeElement($userAction)) {
            // set the owning side to null (unless already changed)
            if ($userAction->getUserId() === $this) {
                $userAction->setUserId(null);
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

    public function addAttendance(Attendance $attendance): static
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances->add($attendance);
            $attendance->setStudent($this);
        }

        return $this;
    }

    public function removeAttendance(Attendance $attendance): static
    {
        if ($this->attendances->removeElement($attendance)) {
            // set the owning side to null (unless already changed)
            if ($attendance->getStudent() === $this) {
                $attendance->setStudent(null);
            }
        }

        return $this;
    }
}
