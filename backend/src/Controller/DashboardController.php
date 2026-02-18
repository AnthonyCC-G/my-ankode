<?php

/**
 * DASHBOARDCONTROLLER.PHP - Page d'accueil avec statistiques utilisateur
 * 
 * Responsabilités :
 * - Afficher le tableau de bord principal après connexion
 * - Agréger les statistiques des 4 modules (Veille, Kanban, Snippets, Compétences)
 * - Récupérer les données depuis PostgreSQL et MongoDB
 * - Afficher les 3 dernières tâches créées
 * 
 * Architecture :
 * - Données hybrides : PostgreSQL (Projects, Tasks, Competences) + MongoDB (Articles, Snippets)
 * - Méthodes de repository personnalisées pour les comptages
 * - Statistiques personnelles à l'utilisateur connecté uniquement
 */

namespace App\Controller;

use App\Repository\CompetenceRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    // ===== 1. AFFICHAGE DU TABLEAU DE BORD AVEC STATISTIQUES =====
    
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        DocumentManager $documentManager,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        CompetenceRepository $competenceRepository
    ): Response
    {
        // 1a. Récupération de l'utilisateur connecté
        $user = $this->getUser();
        
        // ===== 1b. STATS VEILLE (MongoDB) =====
        // Récupération du repository MongoDB pour les articles RSS
        $articleRepository = $documentManager->getRepository(\App\Document\Article::class);
        
        // Comptage des articles favoris de l'utilisateur (méthode custom repository)
        $favoritesCount = $articleRepository->countFavoritesByUser($user);
        
        // Comptage des articles lus par l'utilisateur (méthode custom repository)
        $readCount = $articleRepository->countReadByUser($user);
        
        // ===== 1c. STATS KANBAN (PostgreSQL) =====
        // Comptage du nombre total de projets appartenant à l'utilisateur
        $projectCount = $projectRepository->count(['owner' => $user]);
        
        // Comptage du nombre total de tâches de l'utilisateur (via méthode custom)
        $taskCount = $taskRepository->countByUser($user);
        
        // Récupération des 3 dernières tâches créées (pour affichage rapide)
        $latestTasks = $taskRepository->findLatestByUser($user, 3);

        // ===== 1d. STATS SNIPPETS (MongoDB) =====
        // Récupération du repository MongoDB pour les snippets de code
        $snippetRepository = $documentManager->getRepository(\App\Document\Snippet::class);
        
        // Comptage du nombre total de snippets de l'utilisateur (méthode custom repository)
        $snippetCount = $snippetRepository->countByUser($user);

        // ===== 1e. STATS COMPETENCES (PostgreSQL) =====
        // Comptage du nombre total de compétences appartenant à l'utilisateur
        $competenceCount = $competenceRepository->count(['owner' => $user]);

        
        // 1f. Rendu du template Twig avec toutes les statistiques agrégées
        return $this->render('dashboard/index.html.twig', [
            'favoritesCount' => $favoritesCount,    // Articles favoris (MongoDB)
            'readCount' => $readCount,              // Articles lus (MongoDB)
            'projectCount' => $projectCount,        // Projets (PostgreSQL)
            'taskCount' => $taskCount,              // Tâches (PostgreSQL)
            'latestTasks' => $latestTasks,          // 3 dernières tâches (PostgreSQL)
            'snippetCount' => $snippetCount,        // Snippets (MongoDB)
            'competenceCount' => $competenceCount,  // Compétences (PostgreSQL)
        ]);



    }
}