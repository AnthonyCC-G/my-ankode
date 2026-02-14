<?php

namespace App\Controller;

use App\Document\Snippet;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API REST pour la gestion des snippets de code
 * Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 */
#[Route('/api/snippets')]
#[IsGranted('ROLE_USER')]
class SnippetController extends AbstractController
{
    public function __construct(
        private DocumentManager $dm
    ) {}

    /**
     * GET /api/snippets - Liste des snippets de l'utilisateur connecté
     */
    #[Route('', name: 'api_snippets_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Récupérer les snippets de cet utilisateur
        $snippets = $this->dm->getRepository(Snippet::class)
            ->findBy(['userId' => (string) $user->getId()]);

        return $this->json($snippets, 200);
    }

    /**
     * GET /api/snippets/{id} - Détail d'un snippet
     * 
     * Securite : ResourceVoter verifie l'ownership MongoDB via getUserId()
     */
    #[Route('/{id}', name: 'api_snippets_show', methods: ['GET'])]
    #[IsGranted('VIEW', subject: 'snippet')]
    public function show(Snippet $snippet): JsonResponse
    {
        return $this->json($snippet, 200);
    }

    /**
     * POST /api/snippets - Créer un snippet
     * Protection CSRF  //
     */
    #[Route('', name: 'api_snippets_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Récupérer les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Validation des champs obligatoires
        if (empty($data['title'])) {
            return $this->json(['error' => 'Le titre est obligatoire'], 400);
        }
        
        if (empty($data['language'])) {
            return $this->json(['error' => 'Le langage est obligatoire'], 400);
        }
        
        if (empty($data['code'])) {
            return $this->json(['error' => 'Le code est obligatoire'], 400);
        }

        // Créer le nouveau snippet
        $snippet = new Snippet();
        $snippet->setUserId((string) $user->getId());
        $snippet->setTitle($data['title']);
        $snippet->setLanguage($data['language']);
        $snippet->setCode($data['code']);
        $snippet->setDescription($data['description'] ?? ''); // Description optionnelle

        // Sauvegarder dans MongoDB
        $this->dm->persist($snippet);
        $this->dm->flush();

        return $this->json($snippet, 201);
    }

    /**
     * PUT /api/snippets/{id} - Modifier un snippet
     * Protection CSRF
     * 
     * Securite : ResourceVoter verifie l'ownership MongoDB via getUserId()
     */
    #[Route('/{id}', name: 'api_snippets_update', methods: ['PUT'])]
    #[IsGranted('EDIT', subject: 'snippet')]
    public function update(Snippet $snippet, Request $request): JsonResponse
    {
        // Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Mettre à jour les champs (uniquement ceux fournis)
        if (isset($data['title'])) {
            $snippet->setTitle($data['title']);
        }
        
        if (isset($data['language'])) {
            $snippet->setLanguage($data['language']);
        }
        
        if (isset($data['code'])) {
            $snippet->setCode($data['code']);
        }
        
        if (isset($data['description'])) {
            $snippet->setDescription($data['description']);
        }

        // Sauvegarder les modifications
        $this->dm->flush();

        return $this->json($snippet, 200);
    }

    /**
     * DELETE /api/snippets/{id} - Supprimer un snippet
     * Protection CSRF
     * 
     * Securite : ResourceVoter verifie l'ownership MongoDB via getUserId()
     */
    #[Route('/{id}', name: 'api_snippets_delete', methods: ['DELETE'])]
    #[IsGranted('DELETE', subject: 'snippet')]
    public function delete(Snippet $snippet): JsonResponse
    {
        // Supprimer le snippet
        $this->dm->remove($snippet);
        $this->dm->flush();

        return $this->json(['message' => 'Snippet supprimé avec succès'], 200);
    }
}