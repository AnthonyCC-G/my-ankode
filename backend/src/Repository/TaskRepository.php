<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Compte le nombre total de tâches d'un utilisateur
     * 
     * @param User $user Utilisateur connecté
     * @return int
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les dernières tâches d'un utilisateur
     * Triées par date de création (plus récentes en premier)
     * 
     * @param User $user Utilisateur connecté
     * @param int $limit Nombre de tâches à récupérer
     * @return Task[]
     */
    public function findLatestByUser(User $user, int $limit = 3): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
