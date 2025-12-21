<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class TaskController extends AbstractController
{
    /**
     * Route 1 : Récupérer toutes les tâches d'un projet
     * GET /api/projects/{id}/tasks
     */
    #[Route('/projects/{id}/tasks', methods: ['GET'])]
    public function getTasks(int $id, ProjectRepository $projectRepo): JsonResponse
    {
        // Récupération du projet
        $project = $projectRepo->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }
        
        // Récupération des tâches du projet
        $tasks = $project->getTasks();
        
        // Transformation en tableau JSON
        $data = [];
        foreach ($tasks as $task) {
            $data[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'position' => $task->getPosition(),
                'createdAt' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                'projectId' => $task->getProject()->getId()
            ];
        }
        
        return $this->json($data);
    }
    
    /**
     * Route 2 : Changer le statut d'une tâche
     * PATCH /api/tasks/{id}/status
     */
    #[Route('/tasks/{id}/status', methods: ['PATCH'])]
    public function updateStatus(
        int $id, 
        Request $request, 
        TaskRepository $taskRepo,
        EntityManagerInterface $em
    ): JsonResponse
    {
        // Récupération de la tâche
        $task = $taskRepo->find($id);
        
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        
        // Récupération du nouveau statut depuis le JSON envoyé
        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;
        
        // Validation du statut (doit être 'todo', 'in_progress' ou 'done')
        $validStatuses = ['todo', 'in_progress', 'done'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->json([
                'error' => 'Statut invalide',
                'message' => 'Le statut doit être : todo, in_progress ou done'
            ], 400);
        }
        
        // Mise à jour du statut
        $task->setStatus($newStatus);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(),
                'position' => $task->getPosition()
            ]
        ]);
    }
    
    /**
     * Route 3 : Créer une nouvelle tâche 
     * POST /api/projects/{id}/tasks
     */
    #[Route('/projects/{id}/tasks', methods: ['POST'])]
    public function createTask(
        int $id,
        Request $request,
        ProjectRepository $projectRepo,
        EntityManagerInterface $em
    ): JsonResponse
    {
        // Récupération du projet
        $project = $projectRepo->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }
        
        // Récupération des données envoyées
        $data = json_decode($request->getContent(), true);
        
        // Validation minimale
        if (empty($data['title'])) {
            return $this->json(['error' => 'Le titre est obligatoire'], 400);
        }
        
        // Création de la tâche
        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? null);
        $task->setStatus($data['status'] ?? 'todo'); // Par défaut : todo
        $task->setPosition($data['position'] ?? 0);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        
        // Sauvegarde
        $em->persist($task);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Tâche créée avec succès',
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(),
                'position' => $task->getPosition()
            ]
        ], 201); // Code HTTP 201 = Created
    }
}