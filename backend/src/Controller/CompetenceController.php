<?php

namespace App\Controller;

use App\Entity\Competence;
use App\Repository\CompetenceRepository;
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
        private CompetenceRepository $competenceRepository
    ) {}

    /**
     * Route 1 : Recuperer toutes les competences de l'utilisateur
     * GET /api/competences
     */
    #[Route('', name: 'api_competences_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $owner = $this->getUser();
        $competences = $this->competenceRepository->findBy(
            ['owner' => $owner],
            ['createdAt' => 'DESC'] // Tri par date (plus recent en premier)
        ); 

        $data = array_map(function(Competence $competence) {
            return [
                'id' => $competence->getId(),
                'name' => $competence->getName(),
                'level' => $competence->getLevel(),
                'notes' => $competence->getNotes(),
                'projectsLinks' => $competence->getProjectsLinks(),
                'snippetsLinks' => $competence->getSnippetsLinks(),
                'createdAt' => $competence->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $competences);

        return $this->json($data);
    }

    /**
     * Route 2 : Recuperer une competence specifique
     * GET /api/competences/{id}
     */
    #[Route('/{id}', name: 'api_competences_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $competence = $this->competenceRepository->find($id);

        if (!$competence) {
            return $this->json(['error' => 'Competence non trouvee'], Response::HTTP_NOT_FOUND);
        }

        if ($competence->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Acces interdit'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $competence->getId(),
            'name' => $competence->getName(),
            'level' => $competence->getLevel(),
            'notes' => $competence->getNotes(),
            'projectsLinks' => $competence->getProjectsLinks(),
            'snippetsLinks' => $competence->getSnippetsLinks(),
            'createdAt' => $competence->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Route 3 : Creer une nouvelle competence
     * POST /api/competences
     * Protection CSRF
     */
    #[Route('', name: 'api_competences_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation
        $errors = $this->validateCompetenceData($data);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $competence = new Competence();
        $competence->setName($data['name']);
        $competence->setLevel($data['level']);
        $competence->setNotes($data['notes'] ?? null);
        $competence->setProjectsLinks($data['projectsLinks'] ?? null);
        $competence->setSnippetsLinks($data['snippetsLinks'] ?? null);
        $competence->setOwner($this->getUser());

        $this->entityManager->persist($competence);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Competence creee avec succes',
            'competence' => [
                'id' => $competence->getId(),
                'name' => $competence->getName(),
                'level' => $competence->getLevel(),
                'notes' => $competence->getNotes(),
                'projectsLinks' => $competence->getProjectsLinks(),
                'snippetsLinks' => $competence->getSnippetsLinks(),
                'createdAt' => $competence->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Route 4 : Modifier une competence complete
     * PUT /api/competences/{id}
     * Protection CSRF
     */
    #[Route('/{id}', name: 'api_competences_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $competence = $this->competenceRepository->find($id);

        if (!$competence) {
            return $this->json(['error' => 'Competence non trouvee'], Response::HTTP_NOT_FOUND);
        }

        if ($competence->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Acces interdit'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        $errors = $this->validateCompetenceData($data);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Mise a jour des champs
        $competence->setName($data['name']);
        $competence->setLevel($data['level']);
        $competence->setNotes($data['notes'] ?? null);
        $competence->setProjectsLinks($data['projectsLinks'] ?? null);
        $competence->setSnippetsLinks($data['snippetsLinks'] ?? null);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Competence modifiee avec succes',
            'competence' => [
                'id' => $competence->getId(),
                'name' => $competence->getName(),
                'level' => $competence->getLevel(),
                'notes' => $competence->getNotes(),
                'projectsLinks' => $competence->getProjectsLinks(),
                'snippetsLinks' => $competence->getSnippetsLinks(),
            ]
        ]);
    }

    /**
     * Route 5 : Supprimer une competence
     * DELETE /api/competences/{id}
     * Protection CSRF
     */
    #[Route('/{id}', name: 'api_competences_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $competence = $this->competenceRepository->find($id);

        // Verifier AVANT d'acceder a getOwner()
        if (!$competence) {
            return $this->json(['error' => 'Competence non trouvee'], Response::HTTP_NOT_FOUND);
        }

        if ($competence->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Acces interdit'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($competence);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Competence supprimee avec succes'
        ], Response::HTTP_OK);
    }

    /**
     * Validation des donnees de competence
     */
    private function validateCompetenceData(array $data): array
    {
        $errors = [];

        // Validation name
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est requis';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Le nom ne peut pas depasser 100 caracteres';
        }

        // Validation level
        if (!isset($data['level'])) {
            $errors['level'] = 'Le niveau est requis';
        } elseif (!is_numeric($data['level']) || $data['level'] < 1 || $data['level'] > 5) {
            $errors['level'] = 'Le niveau doit etre un nombre entre 1 et 5';
        }

        // Validation notes (limite de longueur)
        if (isset($data['notes']) && $data['notes'] !== null && strlen($data['notes']) > 1000) {
            $errors['notes'] = 'Les notes ne peuvent pas depasser 1000 caracteres';
        }

        // Validation projectsLinks (text simple)
        if (isset($data['projectsLinks']) && $data['projectsLinks'] !== null && strlen($data['projectsLinks']) > 500) {
            $errors['projectsLinks'] = 'Les liens projets ne peuvent pas depasser 500 caracteres';
        }

        // Validation snippetsLinks (text simple)
        if (isset($data['snippetsLinks']) && $data['snippetsLinks'] !== null && strlen($data['snippetsLinks']) > 500) {
            $errors['snippetsLinks'] = 'Les liens snippets ne peuvent pas depasser 500 caracteres';
        }

        return $errors;
    }
}