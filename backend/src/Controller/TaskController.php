<?php

/**
 * TASKCONTROLLER.PHP - API REST pour la gestion des tâches Kanban
 * 
 * Responsabilités :
 * - CRUD complet des tâches (Create, Read, Update, Delete)
 * - Changement de statut pour le drag & drop Kanban (todo, in_progress, done)
 * - Vérification automatique de l'ownership via ResourceVoter (project.owner)
 * - Validation des données avec Symfony Validator
 * - Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 * 
 * Architecture :
 * - Tâches stockées dans PostgreSQL (Entity\Task)
 * - Une tâche appartient à un projet (ManyToOne)
 * - Ownership indirect : user → project → tasks
 * - ResourceVoter vérifie l'ownership via task.project.owner
 */

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
    // ===== 1. GET - LISTE DE TOUTES LES TÂCHES D'UN PROJET =====
    
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
        // 1a. Récupération de la collection de tâches liées au projet
        // Doctrine hydrate automatiquement la relation OneToMany
        $tasks = $project->getTasks();
        
        // 1b. Transformation de la collection d'entités Task en tableau JSON
        $data = [];
        foreach ($tasks as $task) {
            $data[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(), // todo, in_progress, ou done
                'position' => $task->getPosition(), // Ordre dans la colonne Kanban
                'createdAt' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                'projectId' => $task->getProject()->getId()
            ];
        }
        
        // 1c. Réponse JSON avec la liste des tâches
        return $this->json($data);
    }
    
    // ===== 2. PATCH - CHANGEMENT DE STATUT (DRAG & DROP KANBAN) =====
    
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
        // 2a. Extraction du nouveau statut depuis le JSON body
        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        // 2b. Validation : statut doit être une des 3 valeurs autorisées
        if (!in_array($newStatus, ['todo', 'in_progress', 'done'])) {
            return $this->json(['error' => 'Statut invalide'], 400);
        }

        // 2c. Mise à jour du statut de la tâche (changement de colonne Kanban)
        $task->setStatus($newStatus);
        $em->flush();
        
        // 2d. Réponse JSON avec le nouveau statut
        return $this->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'task' => [
                'id' => $task->getId(),
                'status' => $task->getStatus(),
            ]
        ]);
    }
    
    // ===== 3. POST - CRÉATION D'UNE NOUVELLE TÂCHE =====
    
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
        // 3a. Récupération du projet parent par son ID
        $project = $projectRepo->find($id);

        // 3b. Validation : le projet doit exister
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        // 3c. Vérification manuelle de l'ownership (ResourceVoter ne s'applique pas ici car pas de ParamConverter)
        if ($project->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // 3d. Extraction et décodage du JSON envoyé dans le body
        $data = json_decode($request->getContent(), true);

        // 3e. Validation : titre obligatoire
        if (empty($data['title'])) {
            return $this->json(['error' => 'Le titre est obligatoire'], 400);
        }
        
        // 3f. Validation du statut (par défaut 'todo' si non fourni)
        $status = $data['status'] ?? 'todo';
        $validStatuses = ['todo', 'in_progress', 'done'];
        if (!in_array($status, $validStatuses)) {
            return $this->json([
                'error' => 'Statut invalide',
                'message' => 'Le statut doit être : todo, in_progress ou done'
            ], 400);
        }
        
        // 3g. Création de la nouvelle entité Task
        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? null); // Description optionnelle
        $task->setStatus($status);
        $task->setPosition($data['position'] ?? 0); // Position dans la colonne (défaut 0)
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project); // Association à son projet parent
        
        // 3h. Validation Symfony (contraintes définies dans l'entité Task)
        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        // 3i. Persistance en base de données PostgreSQL
        $em->persist($task);
        $em->flush();
        
        // 3j. Réponse JSON 201 Created avec les données de la tâche créée
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

    // ===== 4. GET - DÉTAILS D'UNE TÂCHE SPÉCIFIQUE =====
    
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
        // 4a. Le ParamConverter Doctrine hydrate automatiquement l'entité Task via {id}
        // 4b. ResourceVoter a déjà vérifié que task.project.owner = utilisateur connecté
        // 4c. Réponse JSON avec les détails de la tâche + informations du projet parent
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

    // ===== 5. PUT - MODIFICATION COMPLÈTE D'UNE TÂCHE =====
    
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
        // 5a. Extraction et décodage du JSON envoyé dans le body
        $data = json_decode($request->getContent(), true);

        // 5b. Validation du statut si fourni dans la requête
        if (isset($data['status'])) {
            $validStatuses = ['todo', 'in_progress', 'done'];
            if (!in_array($data['status'], $validStatuses)) {
                return $this->json([
                    'error' => 'Statut invalide',
                    'message' => 'Le statut doit être : todo, in_progress ou done'
                ], 400);
            }
        }
        
        // 5c. Mise à jour conditionnelle du titre (si présent dans le JSON)
        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }
        
        // 5d. Mise à jour conditionnelle de la description (si présente dans le JSON)
        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }
        
        // 5e. Mise à jour conditionnelle du statut (si présent dans le JSON)
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        
        // 5f. Mise à jour conditionnelle de la position (si présente dans le JSON)
        if (isset($data['position'])) {
            $task->setPosition($data['position']);
        }
        
        // 5g. Validation Symfony après modification
        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        // 5h. Persistance des modifications (pas besoin de persist, l'entité est déjà managée)
        $em->flush();
        
        // 5i. Réponse JSON avec les données mises à jour
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

    // ===== 6. DELETE - SUPPRESSION D'UNE TÂCHE =====
    
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
        // 6a. Suppression de l'entité Task
        $em->remove($task);
        $em->flush();
        
        // 6b. Réponse JSON de confirmation
        return $this->json([
            'success' => true,
            'message' => 'Tâche supprimée avec succès'
        ]);
    }
}