<?php

namespace App\Controller;

use App\Document\Snippet;
use App\Entity\Competence;
use App\Entity\Project;
use App\Repository\CompetenceRepository;
use App\Repository\ProjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API REST pour la gestion des competences
 * Protection CSRF geree automatiquement par CsrfValidationSubscriber
 */
#[Route('/api/competences')]
#[IsGranted('ROLE_USER')]
class CompetenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompetenceRepository $competenceRepository,
        private ProjectRepository $projectRepository,
        private DocumentManager $dm
    ) {}

    /**
     * GET /api/competences - Liste des competences
     */
    #[Route('', name: 'api_competences_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $owner = $this->getUser();
        $competences = $this->competenceRepository->findBy(
            ['owner' => $owner],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function(Competence $competence) {
            return $this->serializeCompetence($competence);
        }, $competences);

        return $this->json($data);
    }

    /**
     * GET /api/competences/{id} - Détail d'une competence
     * 
     * Securite : ResourceVoter verifie automatiquement l'ownership
     */
    #[Route('/{id}', name: 'api_competences_show', methods: ['GET'])]
    #[IsGranted('VIEW', subject: 'competence')]
    public function show(Competence $competence): JsonResponse
    {
        return $this->json($this->serializeCompetence($competence));
    }

    /**
     * POST /api/competences - Créer une competence
     */
    #[Route('', name: 'api_competences_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation basique
        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        $competence = new Competence();
        $competence->setName($data['name']);
        $competence->setDescription($data['description'] ?? null);
        $competence->setOwner($this->getUser());

        // Calcul automatique du niveau (pour l'instant 0, sera mis à jour via édition)
        $competence->calculateLevel();

        $this->entityManager->persist($competence);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Competence creee avec succes',
            'competence' => $this->serializeCompetence($competence)
        ], Response::HTTP_CREATED);
    }

    /**
     * PUT /api/competences/{id} - Modifier une competence
     * 
     * Securite : ResourceVoter verifie automatiquement l'ownership
     */
    #[Route('/{id}', name: 'api_competences_update', methods: ['PUT'])]
    #[IsGranted('EDIT', subject: 'competence')]
    public function update(Competence $competence, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Mise à jour des champs
        if (isset($data['name'])) {
            $competence->setName($data['name']);
        }

        if (isset($data['description'])) {
            $competence->setDescription($data['description']);
        }

        if (isset($data['projectIds'])) {
            // Vider les projets actuels
            foreach ($competence->getProjects() as $project) {
                $competence->removeProject($project);
            }
            
            // Ajouter les nouveaux projets
            foreach ($data['projectIds'] as $projectId) {
                $project = $this->projectRepository->find($projectId);
                if ($project && $project->getOwner() === $this->getUser()) {
                    $competence->addProject($project);
                }
            }
        }

        if (isset($data['snippetsIds'])) {
            $competence->setSnippetsIds($data['snippetsIds']);
        }

        if (isset($data['externalProjects'])) {
            $competence->setExternalProjects($data['externalProjects']);
        }

        if (isset($data['externalSnippets'])) {
            $competence->setExternalSnippets($data['externalSnippets']);
        }

        // Recalculer le niveau automatiquement
        $competence->calculateLevel();

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Competence modifiee avec succes',
            'competence' => $this->serializeCompetence($competence)
        ]);
    }

    /**
     * DELETE /api/competences/{id} - Supprimer une competence
     * 
     * Securite : ResourceVoter verifie automatiquement l'ownership
     */
    #[Route('/{id}', name: 'api_competences_delete', methods: ['DELETE'])]
    #[IsGranted('DELETE', subject: 'competence')]
    public function delete(Competence $competence): JsonResponse
    {
        $this->entityManager->remove($competence);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Competence supprimee avec succes'
        ], Response::HTTP_OK);
    }

    /**
     * Sérialise une competence en tableau
     */
    private function serializeCompetence(Competence $competence): array
    {
        // Projets liés (PostgreSQL)
        $projects = [];
        foreach ($competence->getProjects() as $project) {
            $projects[] = [
                'id' => $project->getId(),
                'name' => $project->getName()
            ];
        }

        // Snippets liés (MongoDB) - récupérer les détails
        $snippets = [];
        foreach ($competence->getSnippetsIds() ?? [] as $snippetId) {
            $snippet = $this->dm->getRepository(Snippet::class)->find($snippetId);
            if ($snippet) {
                $snippets[] = [
                    'id' => $snippet->getId(),
                    'title' => $snippet->getTitle()
                ];
            }
        }

        return [
            'id' => $competence->getId(),
            'name' => $competence->getName(),
            'description' => $competence->getDescription(),
            'level' => (float) $competence->getLevel(),
            'projects' => $projects,
            'snippetsIds' => $competence->getSnippetsIds() ?? [],
            'snippets' => $snippets,
            'externalProjects' => $competence->getExternalProjects(),
            'externalSnippets' => $competence->getExternalSnippets(),
            'createdAt' => $competence->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}