<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\Article;
use App\Document\Snippet;
use App\Repository\ArticleRepository;
use App\Repository\CompetenceRepository;
use App\Repository\ProjectRepository;
use App\Repository\SnippetRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/dashboard')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    /**
     * Page principale du dashboard admin
     */
    #[Route('', name: 'app_admin_dashboard')]
    public function index(Request $request): Response
    {
        // Rediriger les mobiles vers la page desktop-only
        $userAgent = $request->headers->get('User-Agent');
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
            return $this->redirectToRoute('app_desktop_only');
        }

        return $this->render('admin_dashboard/index.html.twig', [
            'controller_name' => 'AdminDashboardController',
        ]);
    }

    /**
     * API - Nombre total d'utilisateurs
     */
    #[Route('/api/stats/users', name: 'api_admin_stats_users', methods: ['GET'])]
    public function getUsersCount(UserRepository $userRepository): JsonResponse
    {
        $count = $userRepository->count([]);
        
        return $this->json([
            'totalUsers' => $count
        ]);
    }

    /**
     * API - Nombre total de projets
     */
    #[Route('/api/stats/projects', name: 'api_admin_stats_projects', methods: ['GET'])]
    public function getProjectsCount(ProjectRepository $projectRepository): JsonResponse
    {
        $count = $projectRepository->count([]);
        
        return $this->json([
            'totalProjects' => $count
        ]);
    }

    /**
     * API - Nombre total de tâches
     */
    #[Route('/api/stats/tasks', name: 'api_admin_stats_tasks', methods: ['GET'])]
    public function getTasksCount(TaskRepository $taskRepository): JsonResponse
    {
        $count = $taskRepository->count([]);
        
        return $this->json([
            'totalTasks' => $count
        ]);
    }

    /**
     * API - Nombre total d'articles de veille (MongoDB)
     */
    #[Route('/api/stats/articles', name: 'api_admin_stats_articles', methods: ['GET'])]
    public function getArticlesCount(DocumentManager $dm): JsonResponse
    {
        // Compter TOUS les articles (pas de filtre userId)
        $count = $dm->createQueryBuilder(Article::class)
            ->count()
            ->getQuery()
            ->execute();
        
        return $this->json([
            'totalArticles' => $count
        ]);
    }

    /**
     * API - Nombre total de snippets (MongoDB)
     */
    #[Route('/api/stats/snippets', name: 'api_admin_stats_snippets', methods: ['GET'])]
    public function getSnippetsCount(DocumentManager $dm): JsonResponse
    {
        // Compter TOUS les snippets (pas de filtre userId)
        $count = $dm->createQueryBuilder(Snippet::class)
            ->count()
            ->getQuery()
            ->execute();
        
        return $this->json([
            'totalSnippets' => $count
        ]);
    }

    /**
     * API - Nombre total de compétences
     */
    #[Route('/api/stats/competences', name: 'api_admin_stats_competences', methods: ['GET'])]
    public function getCompetencesCount(CompetenceRepository $competenceRepository): JsonResponse
    {
        $count = $competenceRepository->count([]);
        
        return $this->json([
            'totalCompetences' => $count
        ]);
    }
}