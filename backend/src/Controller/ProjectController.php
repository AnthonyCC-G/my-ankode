<?php

/**
 * PROJECTCONTROLLER.PHP - API REST pour la gestion des projets Kanban
 * 
 * Responsabilités :
 * - CRUD complet des projets (Create, Read, Update, Delete)
 * - Vérification automatique de l'ownership via ResourceVoter
 * - Validation des données avec Symfony Validator
 * - Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 * 
 * Architecture :
 * - Projets stockés dans PostgreSQL (Entity\Project)
 * - Cascade DELETE automatique sur les tâches liées (Task)
 * - Un projet appartient à un seul utilisateur (owner)
 */

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API REST pour la gestion des projets
 * Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 */
#[Route('/api/projects')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    // ===== 1. GET - LISTE DE TOUS LES PROJETS DE L'UTILISATEUR =====
    
    /**
     * Route 1 : Récupérer tous les projets de l'utilisateur connecté
     * GET /api/projects
     */
    #[Route('', methods: ['GET'])]
    public function getProjects(ProjectRepository $projectRepo): JsonResponse
    {
        // 1a. Récupération de l'utilisateur connecté
        $user = $this->getUser();
        
        // 1b. Requête Doctrine pour récupérer tous les projets où owner = utilisateur connecté
        $projects = $projectRepo->findBy(['owner' => $user]);
        
        // 1c. Transformation des entités Project en tableau JSON
        $data = [];
        foreach ($projects as $project) {
            $data[] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        
        // 1d. Réponse JSON avec la liste des projets
        return $this->json($data);
    }
    
    // ===== 2. GET - DÉTAILS D'UN PROJET SPÉCIFIQUE =====
    
    /**
     * Route 2 : Récupérer un projet spécifique
     * GET /api/projects/{id}
     * 
     * Sécurité : ResourceVoter vérifie automatiquement l'ownership
     */
    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('VIEW', subject: 'project')]
    public function getProject(Project $project): JsonResponse
    {
        // 2a. Le ParamConverter Doctrine hydrate automatiquement l'entité Project via {id}
        // 2b. ResourceVoter a déjà vérifié que $project appartient à l'utilisateur connecté
        // 2c. Réponse JSON avec les détails du projet
        return $this->json([
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
    
    // ===== 3. POST - CRÉATION D'UN NOUVEAU PROJET =====
    
    /**
     * Route 3 : Créer un nouveau projet
     * POST /api/projects
     * Protection CSRF
     */
    #[Route('', methods: ['POST'])]
    public function createProject(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        // 3a. Extraction et décodage du JSON envoyé dans le body de la requête
        $data = json_decode($request->getContent(), true);

        // 3b. Validation : nom obligatoire
        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom du projet est obligatoire'], 400);
        }
        
        // 3c. Création de la nouvelle entité Project
        $project = new Project();
        $project->setName($data['name']);
        $project->setDescription($data['description'] ?? null); // Description optionnelle
        $project->setOwner($this->getUser()); // Attribution automatique au user connecté
        $project->setCreatedAt(new \DateTime()); // Timestamp de création
        
        // 3d. Validation Symfony (contraintes définies dans l'entité Project)
        $errors = $validator->validate($project);
        if (count($errors) > 0) {
            // 3e. Construction du tableau d'erreurs en cas de validation échouée
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        // 3f. Persistance en base de données PostgreSQL
        $em->persist($project);
        $em->flush();
        
        // 3g. Réponse JSON 201 Created avec les données du projet créé
        return $this->json([
            'success' => true,
            'message' => 'Projet créé avec succès',
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
            ]
        ], 201);
    }
    
    // ===== 4. PUT - MODIFICATION D'UN PROJET EXISTANT =====
    
    /**
     * Route 4 : Modifier un projet
     * PUT /api/projects/{id}
     * Protection CSRF
     * 
     * Sécurité : ResourceVoter vérifie automatiquement l'ownership
     */
    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('EDIT', subject: 'project')]
    public function updateProject(
        Project $project,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        // 4a. Extraction et décodage du JSON envoyé dans le body
        $data = json_decode($request->getContent(), true);
        
        // 4b. Mise à jour conditionnelle du nom (si présent dans le JSON)
        if (isset($data['name'])) {
            $project->setName($data['name']);
        }
        
        // 4c. Mise à jour conditionnelle de la description (si présente dans le JSON)
        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }
        
        // 4d. Validation Symfony après modification
        $errors = $validator->validate($project);
        if (count($errors) > 0) {
            // 4e. Construction du tableau d'erreurs en cas de validation échouée
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        // 4f. Persistance des modifications (pas besoin de persist, l'entité est déjà managée)
        $em->flush();
        
        // 4g. Réponse JSON avec les données mises à jour
        return $this->json([
            'success' => true,
            'message' => 'Projet modifié avec succès',
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
            ]
        ]);
    }
    
    // ===== 5. DELETE - SUPPRESSION D'UN PROJET =====
    
    /**
     * Route 5 : Supprimer un projet
     * DELETE /api/projects/{id}
     * Protection CSRF
     * 
     * Sécurité : ResourceVoter vérifie automatiquement l'ownership
     */
    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('DELETE', subject: 'project')]
    public function deleteProject(
        Project $project,
        EntityManagerInterface $em
    ): JsonResponse
    {
        // 5a. Suppression de l'entité Project
        // Les tâches liées sont automatiquement supprimées via CASCADE DELETE (Entity\Task)
        $em->remove($project);
        $em->flush();
        
        // 5b. Réponse JSON de confirmation
        return $this->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
        ]);
    }
}