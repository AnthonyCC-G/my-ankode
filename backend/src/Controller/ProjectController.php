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

/**
 * API REST pour la gestion des projets
 * Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 */
#[Route('/api/projects')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    /**
     * Route 1 : Récupérer tous les projets de l'utilisateur connecté
     * GET /api/projects
     */
    #[Route('', methods: ['GET'])]
    public function getProjects(ProjectRepository $projectRepo): JsonResponse
    {
        $user = $this->getUser();
        $projects = $projectRepo->findBy(['owner' => $user]);
        
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
     * 
     * Sécurité : ResourceVoter vérifie automatiquement l'ownership
     */
    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('VIEW', subject: 'project')]
    public function getProject(Project $project): JsonResponse
    {
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
     * Protection CSRF
     */
    #[Route('', methods: ['POST'])]
    public function createProject(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom du projet est obligatoire'], 400);
        }
        
        $project = new Project();
        $project->setName($data['name']);
        $project->setDescription($data['description'] ?? null);
        $project->setOwner($this->getUser());
        $project->setCreatedAt(new \DateTime());
        
        $errors = $validator->validate($project);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }
        
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
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $project->setName($data['name']);
        }
        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }
        
        $errors = $validator->validate($project);
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
        $em->remove($project);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
        ]);
    }
}