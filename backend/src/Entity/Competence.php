<?php

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

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->projects = new ArrayCollection();
        $this->snippetsIds = [];
        $this->level = 0;
    }

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

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        $this->projects->removeElement($project);

        return $this;
    }

    public function getSnippetsIds(): ?array
    {
        return $this->snippetsIds ?? [];
    }

    public function setSnippetsIds(?array $snippetsIds): static
    {
        $this->snippetsIds = $snippetsIds;

        return $this;
    }

    public function addSnippetId(string $snippetId): static
    {
        if (!in_array($snippetId, $this->snippetsIds ?? [])) {
            $this->snippetsIds[] = $snippetId;
        }

        return $this;
    }

    public function removeSnippetId(string $snippetId): static
    {
        $key = array_search($snippetId, $this->snippetsIds ?? []);
        if ($key !== false) {
            unset($this->snippetsIds[$key]);
            $this->snippetsIds = array_values($this->snippetsIds);
        }

        return $this;
    }

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