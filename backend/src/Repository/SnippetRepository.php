<?php

namespace App\Repository;

use App\Document\Snippet;
use App\Entity\User;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class SnippetRepository extends DocumentRepository
{
    /**
     * Compte le nombre de snippets pour un utilisateur
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals((string) $user->getId())
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * Recupere les N derniers snippets d'un utilisateur
     */
    public function findLatestByUser(User $user, int $limit = 3): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals((string) $user->getId())
            ->sort('createdAt', 'DESC')
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }
}