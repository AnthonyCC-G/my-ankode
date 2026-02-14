<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Project;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; 
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API REST pour la gestion des tâches
 * Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 */
#[Route('/api')]
class TaskController extends AbstractController
{
    /**
     * Route 1 : Récupérer toutes les tâches d'un projet
     * GET /api/projects/{id}/tasks
     * 
     * Securite : ResourceVoter verifie l'ownership du PROJECT (pas des tasks)
     */
    #[Route('/projects/{id}/tasks', methods: ['GET'])]
    #[IsGranted('VIEW', subject: 'project')]
    public function getTasks(Project $project): JsonResponse
    {
        $tasks = $project->getTasks();
        
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
     * Protection CSRF
     * 
     * Securite : ResourceVoter verifie l'ownership via project.owner
     */
    #[Route('/tasks/{id}/status', methods: ['PATCH'])]
    #[IsGranted('EDIT', subject: 'task')]
    public function updateStatus(
        Task $task,
        Request $request, 
        EntityManagerInterface $em
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!in_array($newStatus, ['todo', 'in_progress', 'done'])) {
            return $this->json(['error' => 'Statut invalide'], 400);
        }

        $task->setStatus($newStatus);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'task' => [
                'id' => $task->getId(),
                'status' => $task->getStatus(),
            ]
        ]);
    }
    
    /**
     * Route 3 : Créer une nouvelle tâche 
     * POST /api/projects/{id}/tasks
     * Protection CSRF
     */
    #[Route('/projects/{id}/tasks', methods: ['POST'])]
    public function createTask(
        int $id,
        Request $request,
        ProjectRepository $projectRepo,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $project = $projectRepo->find($id);

        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        if ($project->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['title'])) {
            return $this->json(['error' => 'Le titre est obligatoire'], 400);
        }
        
        $status = $data['status'] ?? 'todo';
        $validStatuses = ['todo', 'in_progress', 'done'];
        if (!in_array($status, $validStatuses)) {
            return $this->json([
                'error' => 'Statut invalide',
                'message' => 'Le statut doit être : todo, in_progress ou done'
            ], 400);
        }
        
        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? null);
        $task->setStatus($status);
        $task->setPosition($data['position'] ?? 0);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        
        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        $em->persist($task);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Tâche créée avec succès',
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'position' => $task->getPosition()
            ]
        ], 201);
    }

    /**
     * Route 4 : Récupérer une tâche spécifique
     * GET /api/tasks/{id}
     * 
     * Securite : ResourceVoter verifie l'ownership via project.owner
     */
    #[Route('/tasks/{id}', methods: ['GET'])]
    #[IsGranted('VIEW', subject: 'task')]
    public function getTask(Task $task): JsonResponse
    {
        return $this->json([
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'position' => $task->getPosition(),
            'createdAt' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
            'projectId' => $task->getProject()->getId(),
            'projectName' => $task->getProject()->getName()
        ]);
    }

    /**
     * Route 5 : Modifier une tâche complète
     * PUT /api/tasks/{id}
     * Protection CSRF
     * 
     * Securite : ResourceVoter verifie l'ownership via project.owner
     */
    #[Route('/tasks/{id}', methods: ['PUT'])]
    #[IsGranted('EDIT', subject: 'task')]
    public function updateTask(
        Task $task,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) {
            $validStatuses = ['todo', 'in_progress', 'done'];
            if (!in_array($data['status'], $validStatuses)) {
                return $this->json([
                    'error' => 'Statut invalide',
                    'message' => 'Le statut doit être : todo, in_progress ou done'
                ], 400);
            }
        }
        
        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        if (isset($data['position'])) {
            $task->setPosition($data['position']);
        }
        
        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Tâche modifiée avec succès',
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'position' => $task->getPosition()
            ]
        ]);
    }

    /**
     * Route 6 : Supprimer une tâche
     * DELETE /api/tasks/{id}
     * Protection CSRF
     * 
     * Securite : ResourceVoter verifie l'ownership via project.owner
     */
    #[Route('/tasks/{id}', methods: ['DELETE'])]
    #[IsGranted('DELETE', subject: 'task')]
    public function deleteTask(
        Task $task,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $em->remove($task);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Tâche supprimée avec succès'
        ]);
    }
}