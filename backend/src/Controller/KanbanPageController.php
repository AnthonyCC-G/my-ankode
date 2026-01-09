<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class KanbanPageController extends AbstractController
{
    #[Route('/kanban', name: 'app_kanban', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        // Récupère tous les projets de l'utilisateur connecté
        $projects = $projectRepository->findBy(
            ['owner' => $this->getUser()],
            ['createdAt' => 'DESC']  // Tri par date décroissante
        );

        // Organise les tâches par statut pour chaque projet
        $projectsWithTasks = [];
        foreach ($projects as $project) {
            $tasks = $project->getTasks();
            
            // Sépare les tâches par statut
            $tasksByStatus = [
                'todo' => [],
                'in_progress' => [],
                'done' => []
            ];

            foreach ($tasks as $task) {
                $status = $task->getStatus();
                if (isset($tasksByStatus[$status])) {
                    $tasksByStatus[$status][] = $task;
                }
            }

            // Trie chaque colonne par position
            foreach ($tasksByStatus as $status => $taskList) {
                usort($tasksByStatus[$status], function($a, $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });
            }

            $projectsWithTasks[] = [
                'project' => $project,
                'tasks' => $tasksByStatus
            ];
        }

        return $this->render('kanban/list.html.twig', [
            'projectsWithTasks' => $projectsWithTasks,
        ]);
    }
}