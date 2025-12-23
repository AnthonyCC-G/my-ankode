<?php

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

#[Route('/api/projects')]
#[IsGranted('ROLE_USER')] // Seuls les utilisateurs connectés peuvent accéder
class ProjectController extends AbstractController
{
    /**
     * Route 1 : Récupérer tous les projets de l'utilisateur connecté
     * GET /api/projects
     */
    #[Route('', methods: ['GET'])]
    public function getProjects(ProjectRepository $projectRepo): JsonResponse
    {
        // Récupérer uniquement les projets de l'utilisateur connecté
        $user = $this->getUser();
        $projects = $projectRepo->findBy(['owner' => $user]);
        
        // Transformation en tableau JSONexit
        $data = [];
        foreach ($projects as $project) {
            $data[] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        
        return $this->json($data);
    }
    
    /**
     * Route 2 : Récupérer un projet spécifique
     * GET /api/projects/{id}
     */
    #[Route('/{id}', methods: ['GET'])]
    public function getProject(int $id, ProjectRepository $projectRepo): JsonResponse
    {
        $project = $projectRepo->find($id);
        
        // Vérifier que le projet existe
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }
        
        // Vérifier que le projet appartient à l'utilisateur connecté
        if ($project->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        return $this->json([
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Route 3 : Créer un nouveau projet
     * POST /api/projects
     */
    #[Route('', methods: ['POST'])]
    public function createProject(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        // Récupération des données JSON
        $data = json_decode($request->getContent(), true);
        
        // Validation minimale
        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom du projet est obligatoire'], 400);
        }
        
        // Création du projet
        $project = new Project();
        $project->setName($data['name']);
        $project->setDescription($data['description'] ?? null);
        $project->setOwner($this->getUser());
        $project->setCreatedAt(new \DateTime());
        
        // Validation avec les contraintes Assert
        $errors = $validator->validate($project);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
        // Sauvegarde
        $em->persist($project);
        $em->flush();
        
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
    
    /**
     * Route 4 : Modifier un projet
     * PUT /api/projects/{id}
     */
    #[Route('/{id}', methods: ['PUT'])]
    public function updateProject(
        int $id,
        Request $request,
        ProjectRepository $projectRepo,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $project = $projectRepo->find($id);
        
        // Vérifier que le projet existe
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }
        
        // Vérifier que le projet appartient à l'utilisateur connecté
        if ($project->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        // Récupération des données JSON
        $data = json_decode($request->getContent(), true);
        
        // Mise à jour des champs
        if (isset($data['name'])) {
            $project->setName($data['name']);
        }
        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }
        
        // Validation avec les contraintes Assert
        $errors = $validator->validate($project);
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
            'message' => 'Projet modifié avec succès',
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
            ]
        ]);
    }
    
    /**
     * Route 5 : Supprimer un projet
     * DELETE /api/projects/{id}
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteProject(
        int $id,
        ProjectRepository $projectRepo,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $project = $projectRepo->find($id);
        
        // Vérifier que le projet existe
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }
        
        // Vérifier que le projet appartient à l'utilisateur connecté
        if ($project->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        // Suppression
        $em->remove($project);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
        ]);
    }
}