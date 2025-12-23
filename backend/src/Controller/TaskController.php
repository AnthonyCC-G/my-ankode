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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        // 1 Récupération du projet
        $project = $projectRepo->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }
        
        // 2 Vérification de la sécurité
        if ($project->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        // 3 Récupération des tâches du projet
        $tasks = $project->getTasks();
        
        // 4 Transformation en tableau JSON
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
        // 1 Récupération de la tâche
        $task = $taskRepo->find($id);
        
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        
        // 2 Vérification de la sécurité
        if ($task->getProject()->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        // 3 Récupération du nouveau statut depuis le JSON envoyé
        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;
        
        // 4 Validation du statut (doit être 'todo', 'in_progress' ou 'done')
        $validStatuses = ['todo', 'in_progress', 'done'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->json([
                'error' => 'Statut invalide',
                'message' => 'Le statut doit être : todo, in_progress ou done'
            ], 400);
        }
        
        // 5 Mise à jour du statut
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
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        // 1 Récupération du projet
        $project = $projectRepo->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }
        
        // 2 Vérification de la sécurité
        if ($project->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        // 3 Récupération des données envoyées
        $data = json_decode($request->getContent(), true);
        
        // 4 Validation minimale du titre
        if (empty($data['title'])) {
            return $this->json(['error' => 'Le titre est obligatoire'], 400);
        }
        
        // 5 Validation du status
        $status = $data['status'] ?? 'todo';
        $validStatuses = ['todo', 'in_progress', 'done'];
        if (!in_array($status, $validStatuses)) {
            return $this->json([
                'error' => 'Statut invalide',
                'message' => 'Le statut doit être : todo, in_progress ou done'
            ], 400);
        }
        
        // 6 Création de la tâche
        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? null);
        $task->setStatus($status);
        $task->setPosition($data['position'] ?? 0);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        
        // 7 Validation avec les contraintes Assert de Task.php
        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        // 8 Sauvegarde
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
        ], 201); // Code HTTP 201 = Created
    }

    /**
     * Route 4 : Récupérer une tâche spécifique
     * GET /api/tasks/{id}
     */
    #[Route('/tasks/{id}', methods: ['GET'])]
    public function getTask(int $id, TaskRepository $taskRepo): JsonResponse
    {
        // Récupération de la tâche
        $task = $taskRepo->find($id);
        
        // Vérification existence
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        
        // Vérification ownership (cascade via Project)
        if ($task->getProject()->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        // Retour de la tâche
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
     */
    #[Route('/tasks/{id}', methods: ['PUT'])]
    public function updateTask(
        int $id,
        Request $request,
        TaskRepository $taskRepo,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        // Récupération de la tâche
        $task = $taskRepo->find($id);
        
        // Vérification existence
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        
        // Vérification ownership (cascade via Project)
        if ($task->getProject()->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        // Récupération des données JSON
        $data = json_decode($request->getContent(), true);
        
        // Validation du status AVANT modification (si fourni)
        if (isset($data['status'])) {
            $validStatuses = ['todo', 'in_progress', 'done'];
            if (!in_array($data['status'], $validStatuses)) {
                return $this->json([
                    'error' => 'Statut invalide',
                    'message' => 'Le statut doit être : todo, in_progress ou done'
                ], 400);
            }
        }
        
        // Mise à jour des champs (seulement ceux fournis)
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
        
        // Validation avec les contraintes Assert de Task.php
        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        // Sauvegarde
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
     */
    #[Route('/tasks/{id}', methods: ['DELETE'])]
    public function deleteTask(
        int $id,
        TaskRepository $taskRepo,
        EntityManagerInterface $em
    ): JsonResponse
    {
        // Récupération de la tâche
        $task = $taskRepo->find($id);
        
        // Vérification existence
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        
        // Vérification ownership (cascade via Project)
        if ($task->getProject()->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        // Suppression
        $em->remove($task);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Tâche supprimée avec succès'
        ]);
    }

}