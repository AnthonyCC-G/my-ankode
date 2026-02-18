<?php

namespace App\Repository;

use App\Document\Article;
use App\Entity\User;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Repository pour gérer les articles RSS stockés dans MongoDB
 * Utilise le système readBy/favoritedBy (multi-utilisateurs)
 */
class ArticleRepository extends DocumentRepository
{
    /**
     * Récupère tous les articles RSS publics
     * Triés du plus récent au plus ancien
     * 
     * @return Article[]
     */
    public function findAllPublic(): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals(null) // Articles RSS publics uniquement
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Récupère les articles non lus par un utilisateur
     * (Articles RSS publics que l'utilisateur n'a pas encore lus)
     * 
     * @param User $user Utilisateur connecté
     * @return Article[]
     */
    public function findUnreadByUser(User $user): array
    {
        $userId = (string) $user->getId();
        
        return $this->createQueryBuilder()
            ->field('userId')->equals(null) // Articles RSS publics
            ->field('readBy')->notIn([$userId]) // User PAS dans readBy
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Récupère les articles lus par un utilisateur
     * (Articles RSS publics que l'utilisateur a déjà lus)
     * 
     * @param User $user Utilisateur connecté
     * @return Article[]
     */
    public function findReadByUser(User $user): array
    {
        $userId = (string) $user->getId();
        
        return $this->createQueryBuilder()
            ->field('userId')->equals(null) // Articles RSS publics
            ->field('readBy')->in([$userId]) // User DANS readBy
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Récupère les articles favoris d'un utilisateur
     * 
     * @param User $user Utilisateur connecté
     * @return Article[]
     */
    public function findFavoritesByUser(User $user): array
    {
        $userId = (string) $user->getId();
        
        return $this->createQueryBuilder()
            ->field('userId')->equals(null) // Articles RSS publics
            ->field('favoritedBy')->in([$userId]) // User DANS favoritedBy
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Récupère les articles d'une source spécifique
     * 
     * @param string $source Nom de la source (ex: "Grafikart", "Dev.to")
     * @return Article[]
     */
    public function findBySource(string $source): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals(null) // Articles RSS publics
            ->field('source')->equals($source)
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Récupère les articles d'une source pour un utilisateur (avec statut lu/favori)
     * 
     * @param User $user Utilisateur connecté
     * @param string $source Nom de la source
     * @return Article[]
     */
    public function findBySourceForUser(User $user, string $source): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals(null)
            ->field('source')->equals($source)
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Compte le nombre total d'articles RSS publics
     * 
     * @return int
     */
    public function countAllPublic(): int
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals(null)
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * Compte le nombre d'articles non lus par un utilisateur
     * 
     * @param User $user Utilisateur connecté
     * @return int
     */
    public function countUnreadByUser(User $user): int
    {
        $userId = (string) $user->getId();
        
        return $this->createQueryBuilder()
            ->field('userId')->equals(null)
            ->field('readBy')->notIn([$userId])
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * Compte le nombre d'articles lus par un utilisateur
     * 
     * @param User $user Utilisateur connecté
     * @return int
     */
    public function countReadByUser(User $user): int
    {
        $userId = (string) $user->getId();
        
        return $this->createQueryBuilder()
            ->field('userId')->equals(null)
            ->field('readBy')->in([$userId])
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * Compte le nombre d'articles favoris d'un utilisateur
     * 
     * @param User $user Utilisateur connecté
     * @return int
     */
    public function countFavoritesByUser(User $user): int
    {
        $userId = (string) $user->getId();
        
        return $this->createQueryBuilder()
            ->field('userId')->equals(null)
            ->field('favoritedBy')->in([$userId])
            ->count()
            ->getQuery()
            ->execute();
    }
}