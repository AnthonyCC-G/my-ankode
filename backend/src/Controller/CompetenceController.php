<?php

/**
 * COMPETENCECONTROLLER.PHP - API REST pour la gestion des compétences
 * 
 * Responsabilités :
 * - CRUD complet des compétences (Create, Read, Update, Delete)
 * - Calcul automatique du niveau de maîtrise (0-5 étoiles)
 * - Liaison avec projets PostgreSQL (ManyToMany)
 * - Liaison avec snippets MongoDB (array d'IDs)
 * - Support des projets/snippets externes (URLs)
 * - Vérification automatique de l'ownership via ResourceVoter
 * - Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 * 
 * Architecture :
 * - Compétences stockées dans PostgreSQL (Entity\Competence)
 * - Relations ManyToMany avec Projects (PostgreSQL)
 * - Références vers Snippets via array d'IDs MongoDB
 * - Niveau calculé automatiquement selon nombre de projets/snippets
 * - Ownership vérifié via competence.owner
 */

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
    // ===== 1. INJECTION DE DÉPENDANCES (HYBRID DATABASE) =====
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompetenceRepository $competenceRepository,
        private ProjectRepository $projectRepository,
        private DocumentManager $dm
    ) {}

    // ===== 2. GET - LISTE DE TOUTES LES COMPÉTENCES DE L'UTILISATEUR =====
    
    /**
     * GET /api/competences - Liste des competences
     */
    #[Route('', name: 'api_competences_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // 2a. Récupération de l'utilisateur connecté
        $owner = $this->getUser();
        
        // 2b. Requête Doctrine pour récupérer toutes les compétences de l'utilisateur
        // Tri par date de création décroissante (plus récentes en premier)
        $competences = $this->competenceRepository->findBy(
            ['owner' => $owner],
            ['createdAt' => 'DESC']
        );

        // 2c. Transformation des entités Competence en tableaux JSON
        // Utilisation de array_map avec la méthode de sérialisation privée
        $data = array_map(function(Competence $competence) {
            return $this->serializeCompetence($competence);
        }, $competences);

        // 2d. Réponse JSON avec la liste des compétences sérialisées
        return $this->json($data);
    }

    // ===== 3. GET - DÉTAILS D'UNE COMPÉTENCE SPÉCIFIQUE =====
    
    /**
     * GET /api/competences/{id} - Détail d'une competence
     * 
     * Securite : ResourceVoter verifie automatiquement l'ownership
     */
    #[Route('/{id}', name: 'api_competences_show', methods: ['GET'])]
    #[IsGranted('VIEW', subject: 'competence')]
    public function show(Competence $competence): JsonResponse
    {
        // 3a. Le ParamConverter Doctrine hydrate automatiquement l'entité Competence via {id}
        // 3b. ResourceVoter a déjà vérifié que competence.owner = utilisateur connecté
        // 3c. Réponse JSON avec les détails complets (projets, snippets, niveau)
        return $this->json($this->serializeCompetence($competence));
    }

    // ===== 4. POST - CRÉATION D'UNE NOUVELLE COMPÉTENCE =====
    
    /**
     * POST /api/competences - Créer une competence
     */
    #[Route('', name: 'api_competences_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // 4a. Extraction et décodage du JSON envoyé dans le body de la requête
        $data = json_decode($request->getContent(), true);

        // 4b. Validation : nom obligatoire
        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        // 4c. Création de la nouvelle entité Competence
        $competence = new Competence();
        $competence->setName($data['name']);
        $competence->setDescription($data['description'] ?? null); // Description optionnelle
        $competence->setOwner($this->getUser()); // Attribution automatique au user connecté

        // 4d. Calcul automatique du niveau initial (0 car aucun projet/snippet lié)
        // Méthode calculateLevel() définie dans l'entité Competence
        $competence->calculateLevel();

        // 4e. Persistance en base de données PostgreSQL
        $this->entityManager->persist($competence);
        $this->entityManager->flush();

        // 4f. Réponse JSON 201 Created avec la compétence sérialisée
        return $this->json([
            'success' => true,
            'message' => 'Competence creee avec succes',
            'competence' => $this->serializeCompetence($competence)
        ], Response::HTTP_CREATED);
    }

    // ===== 5. PUT - MODIFICATION D'UNE COMPÉTENCE EXISTANTE =====
    
    /**
     * PUT /api/competences/{id} - Modifier une competence
     * 
     * Securite : ResourceVoter verifie automatiquement l'ownership
     */
    #[Route('/{id}', name: 'api_competences_update', methods: ['PUT'])]
    #[IsGranted('EDIT', subject: 'competence')]
    public function update(Competence $competence, Request $request): JsonResponse
    {
        // 5a. Extraction et décodage du JSON envoyé dans le body
        $data = json_decode($request->getContent(), true);

        // 5b. Mise à jour conditionnelle du nom (si présent dans le JSON)
        if (isset($data['name'])) {
            $competence->setName($data['name']);
        }

        // 5c. Mise à jour conditionnelle de la description (si présente dans le JSON)
        if (isset($data['description'])) {
            $competence->setDescription($data['description']);
        }

        // 5d. Mise à jour de la relation ManyToMany avec Projects (PostgreSQL)
        if (isset($data['projectIds'])) {
            // 5d1. Vider les projets actuellement liés
            foreach ($competence->getProjects() as $project) {
                $competence->removeProject($project);
            }
            
            // 5d2. Ajouter les nouveaux projets (avec vérification d'ownership)
            foreach ($data['projectIds'] as $projectId) {
                $project = $this->projectRepository->find($projectId);
                // Sécurité : vérifier que le projet appartient bien à l'utilisateur
                if ($project && $project->getOwner() === $this->getUser()) {
                    $competence->addProject($project);
                }
            }
        }

        // 5e. Mise à jour des IDs de snippets MongoDB (array simple d'IDs)
        if (isset($data['snippetsIds'])) {
            $competence->setSnippetsIds($data['snippetsIds']);
        }

        // 5f. Mise à jour des projets externes (array d'URLs ou noms)
        if (isset($data['externalProjects'])) {
            $competence->setExternalProjects($data['externalProjects']);
        }

        // 5g. Mise à jour des snippets externes (array d'URLs ou noms)
        if (isset($data['externalSnippets'])) {
            $competence->setExternalSnippets($data['externalSnippets']);
        }

        // 5h. Recalcul automatique du niveau selon les projets/snippets liés
        // Le niveau évolue de 0 à 5 étoiles selon le nombre total de ressources
        $competence->calculateLevel();

        // 5i. Persistance des modifications
        $this->entityManager->flush();

        // 5j. Réponse JSON avec la compétence mise à jour et son nouveau niveau
        return $this->json([
            'success' => true,
            'message' => 'Competence modifiee avec succes',
            'competence' => $this->serializeCompetence($competence)
        ]);
    }

    // ===== 6. DELETE - SUPPRESSION D'UNE COMPÉTENCE =====
    
    /**
     * DELETE /api/competences/{id} - Supprimer une competence
     * 
     * Securite : ResourceVoter verifie automatiquement l'ownership
     */
    #[Route('/{id}', name: 'api_competences_delete', methods: ['DELETE'])]
    #[IsGranted('DELETE', subject: 'competence')]
    public function delete(Competence $competence): JsonResponse
    {
        // 6a. Suppression de l'entité Competence
        // Les relations ManyToMany avec Projects sont automatiquement nettoyées
        $this->entityManager->remove($competence);
        $this->entityManager->flush();

        // 6b. Réponse JSON de confirmation
        return $this->json([
            'success' => true,
            'message' => 'Competence supprimee avec succes'
        ], Response::HTTP_OK);
    }

    // ===== 7. SERIALISATION HYBRID DATABASE (PostgreSQL + MongoDB) =====
    
    /**
     * Sérialise une competence en tableau
     */
    private function serializeCompetence(Competence $competence): array
    {
        // 7a. Sérialisation des projets liés (PostgreSQL ManyToMany)
        $projects = [];
        foreach ($competence->getProjects() as $project) {
            $projects[] = [
                'id' => $project->getId(),
                'name' => $project->getName()
            ];
        }

        // 7b. Récupération des détails des snippets depuis MongoDB
        // Requêtes individuelles par snippetId pour hydratation complète
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

        // 7c. Construction du tableau JSON complet avec données PostgreSQL + MongoDB
        return [
            'id' => $competence->getId(),
            'name' => $competence->getName(),
            'description' => $competence->getDescription(),
            'level' => (float) $competence->getLevel(),                    // Niveau calculé (0-5)
            'projects' => $projects,                                       // Projets PostgreSQL hydratés
            'snippetsIds' => $competence->getSnippetsIds() ?? [],          // IDs MongoDB bruts
            'snippets' => $snippets,                                       // Snippets MongoDB hydratés
            'externalProjects' => $competence->getExternalProjects(),      // Projets externes (URLs)
            'externalSnippets' => $competence->getExternalSnippets(),      // Snippets externes (URLs)
            'createdAt' => $competence->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}