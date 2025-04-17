<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Model\Planner;
use DateTime;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\ManyToMany(targetEntity: Recipe::class, mappedBy: 'recipeNote')]
    private Collection $UserNote;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $userImg = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 255)]
    private ?string $userName = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $userPlanners = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastAttempt = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $failedAttempts = null;

    public function __construct()
    {
        $this->UserNote = new ArrayCollection();
        $this->userPlanners = $this->initializePlanners();
        $this->failedAttempts = 0;
    }

    public function initializePlanners(): array
    {
        // Créer les planners avec les dates appropriées
        return [
            new Planner(
                'active',
                (new \DateTime())->modify('last monday')->format('Y-m-d'),
                (new \DateTime())->modify('last monday')->modify('+6 days')->format('Y-m-d'),
            ),
            new Planner(
                'expired',
                (new \DateTime())->modify('last monday -1 week')->format('Y-m-d'),
                (new \DateTime())->modify('last monday -1 week')->modify('+6 days')->format('Y-m-d'),
            ),
            new Planner(
                'expired',
                (new \DateTime())->modify('last monday -2 weeks')->format('Y-m-d'),
                (new \DateTime())->modify('last monday -2 weeks')->modify('+6 days')->format('Y-m-d'),
            ),
            new Planner(
                'expired',
                (new \DateTime())->modify('last monday -3 weeks')->format('Y-m-d'),
                (new \DateTime())->modify('last monday -3 weeks')->modify('+6 days')->format('Y-m-d'),
            )
        ];
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
     * @return list<string>
     */
    public function getRoles(): array
    {
        // $roles = $this->roles;
        // // guarantee every user at least has ROLE_USER
        // $roles[] = 'ROLE_USER';
        // return array_unique($roles);
        return $this->roles ?? [];
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
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Recipe>
     */
    public function getUserNote(): Collection
    {
        return $this->UserNote;
    }

    public function addUserNote(Recipe $userNote): static
    {
        if (!$this->UserNote->contains($userNote)) {
            $this->UserNote->add($userNote);
            $userNote->addRecipeNote($this);
        }

        return $this;
    }

    public function removeUserNote(Recipe $userNote): static
    {
        if ($this->UserNote->removeElement($userNote)) {
            $userNote->removeRecipeNote($this);
        }

        return $this;
    }

    public function getUserImg(): ?string
    {
        return $this->userImg;
    }

    public function setUserImg(?string $userImg): static
    {
        $this->userImg = $userImg;

        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function getUserPlanners(): ?array
    {
        return $this->userPlanners;
    }

    public function setUserPlanners(?array $userPlanners): static
    {
        $this->userPlanners = $userPlanners;

        return $this;
    }

    public function resetUserPlanners(): static
    {
        $this->setUserPlanners($this->initializePlanners());

        return $this;
    }

    public function shiftPlanners(): static
    {
        // Décaler tous les éléments d'un indice vers la droite
        array_unshift($this->userPlanners, null); // Insérer un `null` à l'index 0, déplaçant tous les autres éléments.
        return $this;
    }
    
    public function removeOldestPlanner(): static
    {
        // Si on a plus de 4 planners, supprimer le plus ancien (index 4)
        if (count($this->userPlanners) > 4) {
            array_pop($this->userPlanners);  // Supprimer l'élément le plus ancien
        }
        return $this;
    }
    public function addActivePlanner($planner): static
    {
        // Étape 1 : Déplacer les éléments pour faire de la place au nouveau planner actif
        $this->shiftPlanners();
        
        // Étape 2 : Ajouter le nouveau planner actif à l'index 0
        $this->userPlanners[0] = $planner;
        
        // Étape 3 : Supprimer le planner expiré le plus ancien si nécessaire
        $this->removeOldestPlanner();
        
        return $this;
    }

    public function getLastAttempt(): ?\DateTimeInterface
    {
        return $this->lastAttempt;
    }

    public function setLastAttempt(?\DateTimeInterface $lastAttempt): static
    {
        $this->lastAttempt = $lastAttempt;

        return $this;
    }

    public function getFailedAttempts(): ?int
    {
        return $this->failedAttempts;
    }

    public function setFailedAttempts(int $failedAttempts): static
    {
        $this->failedAttempts = $failedAttempts;

        return $this;
    }

    public function decreaseFailedAttempts(int $decrease): static{
        $this->failedAttempts = max(0, $this->failedAttempts - $decrease);

        return $this;
    }

    public function resetFailedAttempts(): static{
        $this->failedAttempts = 0;

        return $this;
    }
}
