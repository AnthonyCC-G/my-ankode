<?php

namespace App\Repository;

use App\Document\Article;
use App\Entity\User;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Repository pour gérer les articles RSS stockés dans MongoDB
 */
class ArticleRepository extends DocumentRepository
{
    /**
     * Récupère tous les articles d'un utilisateur
     * Triés du plus récent au plus ancien
     * 
     * @param User $user Utilisateur connecté
     * @return Article[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals((string) $user->getId())
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Récupère les articles non lus d'un utilisateur
     * 
     * @param User $user Utilisateur connecté
     * @return Article[]
     */
    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals((string) $user->getId())
            ->field('isRead')->equals(false)
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Récupère les articles d'une source spécifique
     * 
     * @param User $user Utilisateur connecté
     * @param string $source Nom de la source (ex: "Dev.to")
     * @return Article[]
     */
    public function findByUserAndSource(User $user, string $source): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals((string) $user->getId())
            ->field('source')->equals($source)
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Compte le nombre d'articles non lus
     * 
     * @param User $user Utilisateur connecté
     * @return int
     */
    public function countUnreadByUser(User $user): int
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals((string) $user->getId())
            ->field('isRead')->equals(false)
            ->count()
            ->getQuery()
            ->execute();
    }
}