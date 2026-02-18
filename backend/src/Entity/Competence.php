<?php

/**
 * COMPETENCE.PHP - Entité PostgreSQL représentant une compétence DWWM
 * 
 * Responsabilités :
 * - Suivre les compétences professionnelles du référentiel DWWM
 * - Calculer automatiquement le niveau de maîtrise (0-5 étoiles)
 * - Lier les compétences aux projets PostgreSQL (ManyToMany)
 * - Référencer les snippets MongoDB via array d'IDs
 * - Gérer les projets/snippets externes (URLs ou noms)
 * 
 * Architecture :
 * - Table 'competence' en PostgreSQL
 * - Relation ManyToOne vers User (owner)
 * - Relation ManyToMany vers Project (table de jointure competence_project)
 * - Références vers Snippets MongoDB : array JSON d'IDs
 * - Niveau calculé automatiquement via calculateLevel()
 * 
 * Formule de calcul du niveau :
 * - Projet MY-ANKODE = +1.0 étoile
 * - Snippet MY-ANKODE = +0.5 étoile
 * - Projet externe = +1.0 étoile
 * - Snippet externe = +0.5 étoile
 * - Maximum plafonné à 5.0 étoiles
 * 
 * Sécurité :
 * - Ownership vérifié via ResourceVoter (competence.owner)
 * - Validation des longueurs de champs
 */

namespace App\Entity;

use App\Repository\CompetenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompetenceRepository::class)]
class Competence
{
    // ===== 1. PROPRIÉTÉS DOCTRINE - DONNÉES DE LA COMPÉTENCE =====
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'competences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom de la compétence est obligatoire")]
    #[Assert\Length(
        max: 100,
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $name = null;

    #[ORM\Column(type: 'float')]
    private float $level = 0.0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Project::class)]
    #[ORM\JoinTable(name: 'competence_project')]
    private Collection $projects;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $snippetsIds = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: "Les projets externes ne peuvent pas dépasser {{ limit }} caractères"
    )]
    private ?string $externalProjects = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: "Les snippets externes ne peuvent pas dépasser {{ limit }} caractères"
    )]
    private ?string $externalSnippets = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // ===== 2. CONSTRUCTEUR - INITIALISATION DES VALEURS PAR DÉFAUT =====
    
    public function __construct()
    {
        // Timestamp automatique de création
        $this->createdAt = new \DateTimeImmutable();
        
        // Initialisation de la collection Doctrine (relation ManyToMany)
        $this->projects = new ArrayCollection();
        
        // Initialisation du tableau d'IDs de snippets MongoDB
        $this->snippetsIds = [];
        
        // Niveau initial à 0 (aucun projet/snippet lié)
        $this->level = 0;
    }

    // ===== 3. GETTERS/SETTERS - PROPRIÉTÉS DE BASE =====

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLevel(): float
    {
        return $this->level;
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

    // ===== 4. GESTION DE LA RELATION MANYTOMANY - PROJECTS =====
    
    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        // Ajout uniquement si le projet n'est pas déjà lié
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        // Retrait du projet de la collection ManyToMany
        $this->projects->removeElement($project);

        return $this;
    }

    // ===== 5. GESTION DES SNIPPETS MONGODB (ARRAY D'IDS) =====
    
    public function getSnippetsIds(): ?array
    {
        // Retourne un tableau vide si null (sécurité)
        return $this->snippetsIds ?? [];
    }

    public function setSnippetsIds(?array $snippetsIds): static
    {
        $this->snippetsIds = $snippetsIds;

        return $this;
    }

    public function addSnippetId(string $snippetId): static
    {
        // Ajout uniquement si l'ID n'est pas déjà présent (évite les doublons)
        if (!in_array($snippetId, $this->snippetsIds ?? [])) {
            $this->snippetsIds[] = $snippetId;
        }

        return $this;
    }

    public function removeSnippetId(string $snippetId): static
    {
        // Recherche de la clé du snippet dans le tableau
        $key = array_search($snippetId, $this->snippetsIds ?? []);
        if ($key !== false) {
            // Suppression et réindexation du tableau
            unset($this->snippetsIds[$key]);
            $this->snippetsIds = array_values($this->snippetsIds);
        }

        return $this;
    }

    // ===== 6. GESTION DES PROJETS/SNIPPETS EXTERNES =====
    
    public function getExternalProjects(): ?string
    {
        return $this->externalProjects;
    }

    public function setExternalProjects(?string $externalProjects): static
    {
        $this->externalProjects = $externalProjects;

        return $this;
    }

    public function getExternalSnippets(): ?string
    {
        return $this->externalSnippets;
    }

    public function setExternalSnippets(?string $externalSnippets): static
    {
        $this->externalSnippets = $externalSnippets;

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

    // ===== 7. CALCUL AUTOMATIQUE DU NIVEAU DE MAÎTRISE =====
    
    /**
     * Calcul automatique du niveau basé sur les projets et snippets liés
     */
    public function calculateLevel(): void
    {
        $level = 0.0; // ← float dès le départ
        
        // Projets MY-ANKODE : +1 étoile chacun
        $level += $this->projects->count() * 1.0;
        
        // Snippets MY-ANKODE : +0.5 étoile chacun
        $level += count($this->snippetsIds ?? []) * 0.5;
        
        // Projets externes : +1 étoile AU TOTAL (pas par projet)
        if (!empty($this->externalProjects)) {
            $externalProjectsCount = count(explode("\n", trim($this->externalProjects)));
            $level += $externalProjectsCount * 1.0;
        }
        
        // Snippets externes : +0.5 étoile AU TOTAL (pas par snippet)
        if (!empty($this->externalSnippets)) {
            $externalSnippetsCount = count(explode("\n", trim($this->externalSnippets)));
            $level += $externalSnippetsCount * 0.5;
        }
        
        // Plafond à 5 étoiles
        $this->level = min(5.0, $level);
    }
}