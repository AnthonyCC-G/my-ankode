<?php

/**
 * ARTICLE.PHP - Document MongoDB pour les articles RSS de veille technologique
 * 
 * Responsabilités :
 * - Stocker les articles RSS agrégés (Korben.info, Dev.to, etc.)
 * - Gérer les statuts personnalisés par utilisateur (lu, favori)
 * - Articles publics (userId = null) partagés entre tous les utilisateurs
 * - Stockage dans MongoDB pour ségrégation des données externes
 * 
 * Architecture :
 * - Collection MongoDB 'articles'
 * - Articles publics RSS : userId = null
 * - Statuts personnels : tableaux readBy[] et favoritedBy[] (IDs utilisateurs)
 * - Métadonnées : title, url, description, source, tags, publishedAt
 * - Repository personnalisé : ArticleRepository (méthodes de comptage)
 */

namespace App\Document;

use App\Repository\ArticleRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use DateTimeImmutable;

#[MongoDB\Document(collection: 'articles', repositoryClass: ArticleRepository::class)]
class Article
{
    // ===== 1. PROPRIÉTÉS MONGODB - MÉTADONNÉES DE L'ARTICLE =====
    
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $title = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $url = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $description = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $source = null;

    #[MongoDB\Field(type: 'collection')]
    private array $tags = [];

    #[MongoDB\Field(type: 'date_immutable')]
    private ?DateTimeImmutable $publishedAt = null;

    #[MongoDB\Field(type: 'date_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    // ========================================
    //  Arrays d'utilisateurs
    // ========================================
    
    /**
     * Liste des IDs des utilisateurs ayant lu cet article
     * Exemple: ['user123', 'user456']
     */
    #[MongoDB\Field(type: 'collection')]
    private array $readBy = [];

    /**
     * Liste des IDs des utilisateurs ayant mis cet article en favori
     * Exemple: ['user123', 'user789']
     */
    #[MongoDB\Field(type: 'collection')]
    private array $favoritedBy = [];

    /**
     * ID du propriétaire de l'article
     * - NULL = article public RSS (visible par tous)
     * - string = article personnel d'un utilisateur
     */
    #[MongoDB\Field(type: 'string')]
    private ?string $userId = null;

    // ===== 2. CONSTRUCTEUR - INITIALISATION TIMESTAMP =====
    
    public function __construct()
    {
        // Timestamp automatique de création du document
        $this->createdAt = new DateTimeImmutable();
    }

    // ========================================
    // Getters / Setters BASIQUES
    // ========================================

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    // ========================================
    //  GESTION LECTURE (isRead → readBy)
    // ========================================

    /**
     * Récupère tous les utilisateurs ayant lu cet article
     * @return array
     */
    public function getReadBy(): array
    {
        return $this->readBy;
    }

    /**
     * Vérifie si un utilisateur a déjà lu cet article
     * @param string $userId ID de l'utilisateur
     * @return bool
     */
    public function isReadByUser(string $userId): bool
    {
        // Recherche stricte (===) dans le tableau readBy[]
        return in_array($userId, $this->readBy, true);
    }

    /**
     * Marque l'article comme lu pour un utilisateur
     * @param string $userId ID de l'utilisateur
     * @return self
     */
    public function markAsReadByUser(string $userId): self
    {
        // Ajout uniquement si pas déjà présent (évite les doublons)
        if (!$this->isReadByUser($userId)) {
            $this->readBy[] = $userId;
        }
        return $this;
    }

    /**
     * Marque l'article comme NON lu pour un utilisateur
     * @param string $userId ID de l'utilisateur
     * @return self
     */
    public function markAsUnreadByUser(string $userId): self
    {
        // Filtrage du tableau pour retirer l'userId + réindexation avec array_values
        $this->readBy = array_values(
            array_filter($this->readBy, fn($id) => $id !== $userId)
        );
        return $this;
    }

    /**
     * Toggle l'état de lecture pour un utilisateur
     * @param string $userId ID de l'utilisateur
     * @return self
     */
    public function toggleReadByUser(string $userId): self
    {
        // Si déjà lu → marquer comme non lu, sinon → marquer comme lu
        if ($this->isReadByUser($userId)) {
            $this->markAsUnreadByUser($userId);
        } else {
            $this->markAsReadByUser($userId);
        }
        return $this;
    }

    // ========================================
    //  GESTION FAVORIS (isFavorite → favoritedBy)
    // ========================================

    /**
     * Récupère tous les utilisateurs ayant mis cet article en favori
     * @return array
     */
    public function getFavoritedBy(): array
    {
        return $this->favoritedBy;
    }

    /**
     * Vérifie si un utilisateur a mis cet article en favori
     * @param string $userId ID de l'utilisateur
     * @return bool
     */
    public function isFavoritedByUser(string $userId): bool
    {
        // Recherche stricte (===) dans le tableau favoritedBy[]
        return in_array($userId, $this->favoritedBy, true);
    }

    /**
     * Ajoute l'article aux favoris d'un utilisateur
     * @param string $userId ID de l'utilisateur
     * @return self
     */
    public function addToFavorites(string $userId): self
    {
        // Ajout uniquement si pas déjà en favori (évite les doublons)
        if (!$this->isFavoritedByUser($userId)) {
            $this->favoritedBy[] = $userId;
        }
        return $this;
    }

    /**
     * Retire l'article des favoris d'un utilisateur
     * @param string $userId ID de l'utilisateur
     * @return self
     */
    public function removeFromFavorites(string $userId): self
    {
        // Filtrage du tableau pour retirer l'userId + réindexation avec array_values
        $this->favoritedBy = array_values(
            array_filter($this->favoritedBy, fn($id) => $id !== $userId)
        );
        return $this;
    }

    /**
     * Toggle l'état favori pour un utilisateur
     * @param string $userId ID de l'utilisateur
     * @return self
     */
    public function toggleFavorite(string $userId): self
    {
        // Si déjà en favori → retirer, sinon → ajouter
        if ($this->isFavoritedByUser($userId)) {
            $this->removeFromFavorites($userId);
        } else {
            $this->addToFavorites($userId);
        }
        return $this;
    }

    // ========================================
    //  MÉTHODES UTILITAIRES
    // ========================================

    /**
     * Compte combien d'utilisateurs ont lu cet article
     * @return int
     */
    public function getReadCount(): int
    {
        return count($this->readBy);
    }

    /**
     * Compte combien d'utilisateurs ont mis cet article en favori
     * @return int
     */
    public function getFavoritesCount(): int
    {
        return count($this->favoritedBy);
    }
}