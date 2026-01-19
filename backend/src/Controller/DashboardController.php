<?php

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
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        DocumentManager $documentManager,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        CompetenceRepository $competenceRepository
    ): Response
    {
        $user = $this->getUser();
        
        // STATS VEILLE (MongoDB)
        $articleRepository = $documentManager->getRepository(\App\Document\Article::class);
        $favoritesCount = $articleRepository->countFavoritesByUser($user);
        $readCount = $articleRepository->countReadByUser($user);
        
        // STATS KANBAN (PostgreSQL)
        $projectCount = $projectRepository->count(['owner' => $user]);
        $taskCount = $taskRepository->countByUser($user);
        $latestTasks = $taskRepository->findLatestByUser($user, 3);

        // STATS SNIPPETS (MongoDB)
        $snippetRepository = $documentManager->getRepository(\App\Document\Snippet::class);
        $snippetCount = $snippetRepository->countByUser($user);

        // STATS COMPETENCES (PostgreSQL) 
        $competenceCount = $competenceRepository->count(['owner' => $user]);

        
        return $this->render('dashboard/index.html.twig', [
            'favoritesCount' => $favoritesCount,
            'readCount' => $readCount,
            'projectCount' => $projectCount,
            'taskCount' => $taskCount,
            'latestTasks' => $latestTasks,
            'snippetCount' => $snippetCount,
            'competenceCount' => $competenceCount, 
        ]);



    }
}

