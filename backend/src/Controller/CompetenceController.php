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

#[Route('/api/competences')]
#[IsGranted('ROLE_USER')]
class CompetenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompetenceRepository $competenceRepository
    ) {}

    #[Route('', name: 'api_competences_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $owner = $this->getUser();
        $competences = $this->competenceRepository->findBy(['owner' => $owner]); 

        $data = array_map(function(Competence $competence) {
            return [
                'id' => $competence->getId(),
                'name' => $competence->getName(),
                'level' => $competence->getLevel(),
                'createdAt' => $competence->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $competences);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_competences_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $competence = $this->competenceRepository->find($id);

        if (!$competence) {
            return $this->json(['error' => 'Compétence non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if ($competence->getOwner() !== $this->getUser()) {
        return $this->json(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $competence->getId(),
            'name' => $competence->getName(),
            'level' => $competence->getLevel(),
            'createdAt' => $competence->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

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
        $competence->setOwner($this->getUser());

        $this->entityManager->persist($competence);
        $this->entityManager->flush();

        return $this->json([
            'id' => $competence->getId(),
            'name' => $competence->getName(),
            'level' => $competence->getLevel(),
            'createdAt' => $competence->getCreatedAt()->format('Y-m-d H:i:s'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_competences_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $competence = $this->competenceRepository->find($id);

        if (!$competence) {
            return $this->json(['error' => 'Compétence non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if ($competence->getOwner() !== $this->getUser()) {
        return $this->json(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        $errors = $this->validateCompetenceData($data);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $competence->setName($data['name']);
        $competence->setLevel($data['level']);

        $this->entityManager->flush();

        return $this->json([
            'id' => $competence->getId(),
            'name' => $competence->getName(),
            'level' => $competence->getLevel(),
        ]);
    }

    #[Route('/{id}', name: 'api_competences_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $competence = $this->competenceRepository->find($id);

        if ($competence->getOwner() !== $this->getUser()) {
        return $this->json(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($competence);
        $this->entityManager->flush();

        return $this->json(['message' => 'Compétence supprimée avec succès'], Response::HTTP_OK);
    }

    private function validateCompetenceData(array $data): array
    {
        $errors = [];

        // Validation name
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est requis';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Le nom ne peut pas dépasser 100 caractères';
        }

        // Validation level
        if (!isset($data['level'])) {
            $errors['level'] = 'Le niveau est requis';
        } elseif (!is_numeric($data['level']) || $data['level'] < 1 || $data['level'] > 5) {
            $errors['level'] = 'Le niveau doit être un nombre entre 1 et 5';
        }

        return $errors;
    }
}