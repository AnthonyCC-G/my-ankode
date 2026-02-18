<?php

/**
 * TASK.PHP - Entité PostgreSQL représentant une tâche Kanban
 * 
 * Responsabilités :
 * - Représenter une tâche dans un projet Kanban
 * - Gérer le statut de la tâche (todo, in_progress, done)
 * - Gérer la position pour l'ordre d'affichage dans les colonnes
 * - Lier la tâche à son projet parent (ManyToOne vers Project)
 * 
 * Architecture :
 * - Table 'task' en PostgreSQL
 * - Relation Doctrine : project (ManyToOne vers Project)
 * - Suppression automatique si le projet parent est supprimé (orphanRemoval dans Project)
 * - Contraintes de validation : title obligatoire (max 255 car), description optionnelle (max 1000 car)
 * - Statut validé par Assert\Choice (seulement 3 valeurs possibles)
 * 
 * Sécurité :
 * - Ownership vérifié via ResourceVoter (task.project.owner)
 * - Une tâche ne peut exister sans projet (project NOT NULL)
 */

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    // ===== 1. PROPRIÉTÉS DOCTRINE - DONNÉES DE LA TÂCHE =====
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre de la tâche est obligatoire")]
    #[Assert\Length(
    max: 255,
    maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
    max: 1000,
    maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['todo', 'in_progress', 'done'], message: 'Le statut doit être todo, in_progress ou done.')]
    private ?string $status = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    // ===== 2. GETTERS/SETTERS - PROPRIÉTÉS DE BASE =====

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }
}