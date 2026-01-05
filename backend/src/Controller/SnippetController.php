<?php

namespace App\Controller;

use App\Document\Snippet;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/snippets')]
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
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les snippets de cet utilisateur
        $snippets = $this->dm->getRepository(Snippet::class)
            ->findBy(['userId' => $user->getId()]);

        return $this->json($snippets, Response::HTTP_OK);
    }

    /**
     * GET /api/snippets/{id} - Détail d'un snippet
     */
    #[Route('/{id}', name: 'api_snippets_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer le snippet par son ID
        $snippet = $this->dm->getRepository(Snippet::class)->find($id);

        // Vérifier que le snippet existe
        if (!$snippet) {
            return $this->json(['error' => 'Snippet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est bien le propriétaire
        if ($snippet->getUserId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($snippet, Response::HTTP_OK);
    }

    /**
     * POST /api/snippets - Créer un snippet
     */
    #[Route('', name: 'api_snippets_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Validation des champs obligatoires
        if (empty($data['title'])) {
            return $this->json(['error' => 'Le titre est obligatoire'], Response::HTTP_BAD_REQUEST);
        }
        
        if (empty($data['language'])) {
            return $this->json(['error' => 'Le langage est obligatoire'], Response::HTTP_BAD_REQUEST);
        }
        
        if (empty($data['code'])) {
            return $this->json(['error' => 'Le code est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        // Validation du langage (doit être dans la liste autorisée)
        $allowedLanguages = ['js', 'php', 'html', 'css', 'sql', 'other'];
        if (!in_array($data['language'], $allowedLanguages)) {
            return $this->json([
                'error' => 'Langage invalide. Valeurs autorisées : ' . implode(', ', $allowedLanguages)
            ], Response::HTTP_BAD_REQUEST);
        }

        // Créer le nouveau snippet
        $snippet = new Snippet();
        $snippet->setUserId($user->getId());
        $snippet->setTitle($data['title']);
        $snippet->setLanguage($data['language']);
        $snippet->setCode($data['code']);
        $snippet->setDescription($data['description'] ?? ''); // Description optionnelle

        // Sauvegarder dans MongoDB
        $this->dm->persist($snippet);
        $this->dm->flush();

        return $this->json($snippet, Response::HTTP_CREATED);
    }

    /**
     * PUT /api/snippets/{id} - Modifier un snippet
     */
    #[Route('/{id}', name: 'api_snippets_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer le snippet
        $snippet = $this->dm->getRepository(Snippet::class)->find($id);

        if (!$snippet) {
            return $this->json(['error' => 'Snippet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est bien le propriétaire
        if ($snippet->getUserId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Validation du langage si fourni
        if (isset($data['language'])) {
            $allowedLanguages = ['js', 'php', 'html', 'css', 'sql', 'other'];
            if (!in_array($data['language'], $allowedLanguages)) {
                return $this->json([
                    'error' => 'Langage invalide. Valeurs autorisées : ' . implode(', ', $allowedLanguages)
                ], Response::HTTP_BAD_REQUEST);
            }
        }

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

        return $this->json($snippet, Response::HTTP_OK);
    }

    /**
     * DELETE /api/snippets/{id} - Supprimer un snippet
     */
    #[Route('/{id}', name: 'api_snippets_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer le snippet
        $snippet = $this->dm->getRepository(Snippet::class)->find($id);

        if (!$snippet) {
            return $this->json(['error' => 'Snippet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est bien le propriétaire
        if ($snippet->getUserId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Supprimer le snippet
        $this->dm->remove($snippet);
        $this->dm->flush();

        return $this->json(['message' => 'Snippet supprimé avec succès'], Response::HTTP_OK);
    }
}