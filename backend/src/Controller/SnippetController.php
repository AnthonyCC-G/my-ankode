<?php

/**
 * SNIPPETCONTROLLER.PHP - API REST pour la gestion des snippets de code
 * 
 * Responsabilités :
 * - CRUD complet des snippets (Create, Read, Update, Delete)
 * - Vérification automatique de l'ownership via ResourceVoter MongoDB
 * - Stockage dans MongoDB (Document\Snippet)
 * - Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 * 
 * Architecture :
 * - Snippets stockés dans MongoDB (Document\Snippet)
 * - Ownership via userId stocké en string
 * - ResourceVoter vérifie l'ownership via snippet.getUserId()
 * - Ségrégation des données : code potentiellement dangereux isolé dans MongoDB
 */

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
    // ===== 1. INJECTION DE DÉPENDANCE MONGODB =====
    
    public function __construct(
        private DocumentManager $dm
    ) {}

    // ===== 2. GET - LISTE DE TOUS LES SNIPPETS DE L'UTILISATEUR =====
    
    /**
     * GET /api/snippets - Liste des snippets de l'utilisateur connecté
     */
    #[Route('', name: 'api_snippets_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // 2a. Récupération de l'utilisateur connecté
        $user = $this->getUser();
        
        // 2b. Vérification de l'authentification (sécurité défense en profondeur)
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // 2c. Requête MongoDB pour récupérer tous les snippets de cet utilisateur
        // userId stocké en string dans MongoDB (conversion nécessaire)
        $snippets = $this->dm->getRepository(Snippet::class)
            ->findBy(['userId' => (string) $user->getId()]);

        // 2d. Réponse JSON avec la liste des snippets
        // MongoDB ODM sérialise automatiquement les documents en JSON
        return $this->json($snippets, 200);
    }

    // ===== 3. GET - DÉTAILS D'UN SNIPPET SPÉCIFIQUE =====
    
    /**
     * GET /api/snippets/{id} - Détail d'un snippet
     * 
     * Securite : ResourceVoter verifie l'ownership MongoDB via getUserId()
     */
    #[Route('/{id}', name: 'api_snippets_show', methods: ['GET'])]
    #[IsGranted('VIEW', subject: 'snippet')]
    public function show(Snippet $snippet): JsonResponse
    {
        // 3a. Le ParamConverter MongoDB hydrate automatiquement le document Snippet via {id}
        // 3b. ResourceVoter a déjà vérifié que snippet.userId = utilisateur connecté
        // 3c. Réponse JSON avec les détails du snippet
        return $this->json($snippet, 200);
    }

    // ===== 4. POST - CRÉATION D'UN NOUVEAU SNIPPET =====
    
    /**
     * POST /api/snippets - Créer un snippet
     * Protection CSRF  //
     */
    #[Route('', name: 'api_snippets_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // 4a. Récupération de l'utilisateur connecté
        $user = $this->getUser();
        
        // 4b. Vérification de l'authentification (sécurité défense en profondeur)
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // 4c. Extraction et décodage du JSON envoyé dans le body de la requête
        $data = json_decode($request->getContent(), true);

        // 4d. Validation : titre obligatoire
        if (empty($data['title'])) {
            return $this->json(['error' => 'Le titre est obligatoire'], 400);
        }
        
        // 4e. Validation : langage obligatoire
        if (empty($data['language'])) {
            return $this->json(['error' => 'Le langage est obligatoire'], 400);
        }
        
        // 4f. Validation : code obligatoire
        if (empty($data['code'])) {
            return $this->json(['error' => 'Le code est obligatoire'], 400);
        }

        // 4g. Création du nouveau document MongoDB Snippet
        $snippet = new Snippet();
        $snippet->setUserId((string) $user->getId()); // Stockage de l'ID user en string
        $snippet->setTitle($data['title']);
        $snippet->setLanguage($data['language']);
        $snippet->setCode($data['code']);
        $snippet->setDescription($data['description'] ?? ''); // Description optionnelle (défaut vide)

        // 4h. Persistance dans MongoDB
        $this->dm->persist($snippet);
        $this->dm->flush();

        // 4i. Réponse JSON 201 Created avec le document créé
        return $this->json($snippet, 201);
    }

    // ===== 5. PUT - MODIFICATION D'UN SNIPPET EXISTANT =====
    
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
        // 5a. Extraction et décodage du JSON envoyé dans le body
        $data = json_decode($request->getContent(), true);

        // 5b. Mise à jour conditionnelle du titre (si présent dans le JSON)
        if (isset($data['title'])) {
            $snippet->setTitle($data['title']);
        }
        
        // 5c. Mise à jour conditionnelle du langage (si présent dans le JSON)
        if (isset($data['language'])) {
            $snippet->setLanguage($data['language']);
        }
        
        // 5d. Mise à jour conditionnelle du code (si présent dans le JSON)
        if (isset($data['code'])) {
            $snippet->setCode($data['code']);
        }
        
        // 5e. Mise à jour conditionnelle de la description (si présente dans le JSON)
        if (isset($data['description'])) {
            $snippet->setDescription($data['description']);
        }

        // 5f. Persistance des modifications dans MongoDB
        $this->dm->flush();

        // 5g. Réponse JSON avec le document mis à jour
        return $this->json($snippet, 200);
    }

    // ===== 6. DELETE - SUPPRESSION D'UN SNIPPET =====
    
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
        // 6a. Suppression du document MongoDB Snippet
        $this->dm->remove($snippet);
        $this->dm->flush();

        // 6b. Réponse JSON de confirmation
        return $this->json(['message' => 'Snippet supprimé avec succès'], 200);
    }
}