<?php

/**
 * USER.PHP - Entité PostgreSQL représentant un utilisateur de l'application
 * 
 * Responsabilités :
 * - Authentification Symfony (UserInterface, PasswordAuthenticatedUserInterface)
 * - Gestion des rôles (ROLE_USER par défaut, ROLE_ADMIN pour administrateurs)
 * - Relations avec Projects et Competences (OneToMany avec cascade delete)
 * - Stockage dans PostgreSQL avec contrainte d'unicité sur email
 * 
 * Architecture :
 * - Table 'user_' en PostgreSQL
 * - Identifiant unique : email
 * - Relations Doctrine : projects (OneToMany), competences (OneToMany)
 * - Cascade orphanRemoval : suppression auto des projets/compétences si user supprimé
 * - Sérialisation sécurisée : hash CRC32C du password (Symfony 7.3+)
 * 
 * Sécurité :
 * - Password hashé avec bcrypt via UserPasswordHasher
 * - Email unique en base (contrainte + validation Symfony)
 * - Username unique en base
 * - Rôle ROLE_USER garanti pour tous les utilisateurs
 */

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user_')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ===== 1. PROPRIÉTÉS DOCTRINE - DONNÉES UTILISATEUR =====
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /* champ : username  */
    #[ORM\Column(length: 100, unique: true)]
    private ?string $username = null;

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

    /* champ : created_at   */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $projects;

    /**
     * @var Collection<int, Competence>
     */
    #[ORM\OneToMany(targetEntity: Competence::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $competences;

    // ===== 2. CONSTRUCTEUR - INITIALISATION DES VALEURS PAR DÉFAUT =====
    
    /*  */
    public function __construct()
    {
        // Timestamp automatique de création
        $this->createdAt = new \DateTimeImmutable();
        
        // Rôle ROLE_USER attribué par défaut à tout nouvel utilisateur
        $this->roles = ['ROLE_USER']; // Ici le rôle de l'utilisateur par défaut
        
        // Initialisation des collections Doctrine (relations OneToMany)
        $this->projects = new ArrayCollection();
        $this->competences = new ArrayCollection();
    }

    // ===== 3. GETTERS/SETTERS - PROPRIÉTÉS DE BASE =====

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

    /* GETTER pour username */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /* SETTER pour username  */
    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    // ===== 4. MÉTHODES USERINTERFACE - SYMFONY SECURITY =====
    
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        // L'email sert d'identifiant unique pour l'authentification
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

    // ===== 5. MÉTHODES PASSWORDAUTHENTICATEDUSERINTERFACE =====
    
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

    // ===== 6. SÉRIALISATION SÉCURISÉE (SYMFONY 7.3+) =====
    
    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    // ===== 7. GETTERS/SETTERS - CREATED_AT =====
    
    // GETTER pour created_at
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // SETTER pour created_at
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // ===== 8. MÉTHODE DÉPRÉCIÉE (SYMFONY 8) =====

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    // ===== 9. GESTION DE LA RELATION ONETOMANY - PROJECTS =====
    
    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        // Ajout uniquement si le projet n'est pas déjà dans la collection
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setOwner($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getOwner() === $this) {
                $project->setOwner(null);
            }
        }

        return $this;
    }

    // ===== 10. GESTION DE LA RELATION ONETOMANY - COMPETENCES =====
    
    /**
     * @return Collection<int, Competence>
     */
    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(Competence $competence): static
    {
        // Ajout uniquement si la compétence n'est pas déjà dans la collection
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
            $competence->setOwner($this);
        }

        return $this;
    }

    public function removeCompetence(Competence $competence): static
    {
        if ($this->competences->removeElement($competence)) {
            // set the owning side to null (unless already changed)
            if ($competence->getOwner() === $this) {
                $competence->setOwner(null);
            }
        }

        return $this;
    }
}