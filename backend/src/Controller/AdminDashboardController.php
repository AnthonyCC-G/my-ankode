<?php

/**
 * ADMINDASHBOARDCONTROLLER.PHP - Dashboard administrateur avec statistiques globales
 * 
 * Responsabilités :
 * - Afficher la page HTML du dashboard admin (réservée ROLE_ADMIN)
 * - Fournir 7 endpoints API pour statistiques globales
 * - Comptages PostgreSQL : Users, Projects, Tasks, Competences
 * - Comptages MongoDB : Articles, Snippets
 * - Documentation OpenAPI complète pour chaque endpoint
 * - Détection mobile côté serveur (redirection vers desktop-only)
 * 
 * Architecture :
 * - Accès réservé ROLE_ADMIN uniquement
 * - Données hybrides : PostgreSQL + MongoDB
 * - Pas de filtrage par utilisateur (stats globales)
 * - Annotations OpenAPI pour documentation Swagger
 */

namespace App\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\Article;
use App\Document\Snippet;
use App\Repository\CompetenceRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

    #[Route('/admin/dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Tag(name: 'Admin - Statistiques')]
    class AdminDashboardController extends AbstractController
    {
    // ===== 1. AFFICHAGE DE LA PAGE HTML DASHBOARD ADMIN =====
    
    /**
    * Page principale du dashboard admin
    */
    #[Route('', name: 'app_admin_dashboard')]
    public function index(Request $request): Response
    {
    // 1a. Détection mobile côté serveur via User-Agent
    // Rediriger les mobiles vers la page desktop-only
    $userAgent = $request->headers->get('User-Agent');
    if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
    return $this->redirectToRoute('app_desktop_only');
    }
        
        // 1b. Rendu du template Twig vide (statistiques chargées dynamiquement via API)
        return $this->render('admin_dashboard/index.html.twig', [
            'controller_name' => 'AdminDashboardController',
        ]);
    }

    // ===== 2. API - STATISTIQUES USERS (PostgreSQL) =====
    
    /**
     * API - Nombre total d'utilisateurs
     */
    #[Route('/api/stats/users', name: 'api_admin_stats_users', methods: ['GET'])]
    #[OA\Get(
        path: '/admin/dashboard/api/stats/users',
        summary: 'Nombre total d\'utilisateurs inscrits',
        description: 'Retourne le nombre total d\'utilisateurs enregistrés dans la base PostgreSQL',
        tags: ['Admin - Statistiques']
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistique récupérée avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'totalUsers', type: 'integer', example: 42)
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès refusé - Requiert ROLE_ADMIN'
    )]
    public function getUsersCount(UserRepository $userRepository): JsonResponse
    {
        // 2a. Comptage total des utilisateurs dans PostgreSQL (aucun filtre)
        $count = $userRepository->count([]);
        
        // 2b. Réponse JSON avec le total
        return $this->json([
            'totalUsers' => $count
        ]);
    }

    // ===== 3. API - STATISTIQUES PROJECTS (PostgreSQL) =====
    
    /**
     * API - Nombre total de projets
     */
    #[Route('/api/stats/projects', name: 'api_admin_stats_projects', methods: ['GET'])]
    #[OA\Get(
        path: '/admin/dashboard/api/stats/projects',
        summary: 'Nombre total de projets créés',
        description: 'Retourne le nombre total de projets Kanban stockés dans PostgreSQL',
        tags: ['Admin - Statistiques']
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistique récupérée avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'totalProjects', type: 'integer', example: 15)
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès refusé - Requiert ROLE_ADMIN'
    )]
    public function getProjectsCount(ProjectRepository $projectRepository): JsonResponse
    {
        // 3a. Comptage total des projets dans PostgreSQL (tous utilisateurs confondus)
        $count = $projectRepository->count([]);
        
        // 3b. Réponse JSON avec le total
        return $this->json([
            'totalProjects' => $count
        ]);
    }

    // ===== 4. API - STATISTIQUES TASKS (PostgreSQL) =====
    
    /**
     * API - Nombre total de tâches
     */
    #[Route('/api/stats/tasks', name: 'api_admin_stats_tasks', methods: ['GET'])]
    #[OA\Get(
        path: '/admin/dashboard/api/stats/tasks',
        summary: 'Nombre total de tâches',
        description: 'Retourne le nombre total de tâches tous projets confondus (PostgreSQL)',
        tags: ['Admin - Statistiques']
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistique récupérée avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'totalTasks', type: 'integer', example: 127)
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès refusé - Requiert ROLE_ADMIN'
    )]
    public function getTasksCount(TaskRepository $taskRepository): JsonResponse
    {
        // 4a. Comptage total des tâches dans PostgreSQL (tous projets confondus)
        $count = $taskRepository->count([]);
        
        // 4b. Réponse JSON avec le total
        return $this->json([
            'totalTasks' => $count
        ]);
    }

    // ===== 5. API - STATISTIQUES ARTICLES (MongoDB) =====
    
    /**
     * API - Nombre total d'articles de veille (MongoDB)
     */
    #[Route('/api/stats/articles', name: 'api_admin_stats_articles', methods: ['GET'])]
    #[OA\Get(
        path: '/admin/dashboard/api/stats/articles',
        summary: 'Nombre total d\'articles de veille',
        description: 'Retourne le nombre total d\'articles RSS stockés dans MongoDB',
        tags: ['Admin - Statistiques']
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistique récupérée avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'totalArticles', type: 'integer', example: 234)
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès refusé - Requiert ROLE_ADMIN'
    )]
    public function getArticlesCount(DocumentManager $dm): JsonResponse
    {
        // 5a. Comptage total des articles dans MongoDB
        // Compter TOUS les articles (pas de filtre userId)
        $count = $dm->createQueryBuilder(Article::class)
            ->count()
            ->getQuery()
            ->execute();
        
        // 5b. Réponse JSON avec le total
        return $this->json([
            'totalArticles' => $count
        ]);
    }

    // ===== 6. API - STATISTIQUES SNIPPETS (MongoDB) =====
    
    /**
     * API - Nombre total de snippets (MongoDB)
     */
    #[Route('/api/stats/snippets', name: 'api_admin_stats_snippets', methods: ['GET'])]
    #[OA\Get(
        path: '/admin/dashboard/api/stats/snippets',
        summary: 'Nombre total de snippets de code',
        description: 'Retourne le nombre total de snippets stockés dans MongoDB',
        tags: ['Admin - Statistiques']
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistique récupérée avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'totalSnippets', type: 'integer', example: 89)
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès refusé - Requiert ROLE_ADMIN'
    )]
    public function getSnippetsCount(DocumentManager $dm): JsonResponse
    {
        // 6a. Comptage total des snippets dans MongoDB
        // Compter TOUS les snippets (pas de filtre userId)
        $count = $dm->createQueryBuilder(Snippet::class)
            ->count()
            ->getQuery()
            ->execute();
        
        // 6b. Réponse JSON avec le total
        return $this->json([
            'totalSnippets' => $count
        ]);
    }

    // ===== 7. API - STATISTIQUES COMPETENCES (PostgreSQL) =====
    
    /**
     * API - Nombre total de compétences
     */
    #[Route('/api/stats/competences', name: 'api_admin_stats_competences', methods: ['GET'])]
    #[OA\Get(
        path: '/admin/dashboard/api/stats/competences',
        summary: 'Nombre total de compétences suivies',
        description: 'Retourne le nombre total de compétences DWWM référencées (PostgreSQL)',
        tags: ['Admin - Statistiques']
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistique récupérée avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'totalCompetences', type: 'integer', example: 8)
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès refusé - Requiert ROLE_ADMIN'
    )]
    public function getCompetencesCount(CompetenceRepository $competenceRepository): JsonResponse
    {
        // 7a. Comptage total des compétences dans PostgreSQL (tous utilisateurs confondus)
        $count = $competenceRepository->count([]);
        
        // 7b. Réponse JSON avec le total
        return $this->json([
            'totalCompetences' => $count
        ]);
    }
}