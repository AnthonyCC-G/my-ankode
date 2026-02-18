<?php

/**
 * PROJECT.PHP - Entité PostgreSQL représentant un projet Kanban
 * 
 * Responsabilités :
 * - Représenter un projet Kanban avec nom et description
 * - Gérer la relation avec les tâches (OneToMany avec cascade delete)
 * - Lier le projet à son propriétaire (ManyToOne vers User)
 * - Validation des données (nom obligatoire, limites de caractères)
 * 
 * Architecture :
 * - Table 'project' en PostgreSQL
 * - Relations Doctrine : owner (ManyToOne vers User), tasks (OneToMany vers Task)
 * - Cascade orphanRemoval : suppression auto des tâches si projet supprimé
 * - Contraintes de validation : nom obligatoire (max 255 car), description optionnelle (max 1000 car)
 * 
 * Sécurité :
 * - Ownership vérifié via ResourceVoter (project.owner)
 * - Un projet ne peut avoir qu'un seul propriétaire (owner NOT NULL)
 */

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    // ===== 1. PROPRIÉTÉS DOCTRINE - DONNÉES DU PROJET =====
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)] // ajout de la contrainte pour rendre le nom d'un projet obligatoire
    #[Assert\NotBlank(message: "Le nom du projet est obligatoire")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)] // ajout d'une contrainte de taille de texte "pas plus de 1000 caractères"
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $tasks;

    // ===== 2. CONSTRUCTEUR - INITIALISATION DE LA COLLECTION TASKS =====
    
    public function __construct()
    {
        // Initialisation de la collection Doctrine (relation OneToMany)
        $this->tasks = new ArrayCollection();
    }

    // ===== 3. GETTERS/SETTERS - PROPRIÉTÉS DE BASE =====

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    // ===== 4. GESTION DE LA RELATION ONETOMANY - TASKS =====
    
    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        // Ajout uniquement si la tâche n'est pas déjà dans la collection
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setProject($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getProject() === $this) {
                $task->setProject(null);
            }
        }

        return $this;
    }
}