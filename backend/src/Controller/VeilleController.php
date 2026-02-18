<?php

/**
 * VEILLECONTROLLER.PHP - Gestion de la veille technologique (Articles RSS)
 * 
 * Responsabilités :
 * - Affichage de la page HTML Veille
 * - API REST pour lister, rechercher, filtrer les articles MongoDB
 * - Gestion des favoris et statut "lu" par utilisateur
 * - Pagination et filtrage par source
 * - Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 * 
 * Architecture :
 * - Articles stockés dans MongoDB (Document\Article)
 * - Articles publics (userId = null) partagés entre tous les utilisateurs
 * - Statuts personnels (lu/favori) stockés dans les tableaux readBy/favoritedBy
 */

namespace App\Controller;

use App\Document\Article;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la veille technologique (articles)
 * Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 */
#[IsGranted('ROLE_USER')]
class VeilleController extends AbstractController
{
    // ===== 1. AFFICHAGE DE LA PAGE HTML =====
    
    /**
     * Page HTML - Interface Veille
     */
    #[Route('/veille', name: 'app_veille', methods: ['GET'])]
    public function index(): Response
    {
        // Rendu du template vide
        // Le JavaScript chargera dynamiquement les articles via /api/articles
        return $this->render('veille/list.html.twig');
    }

    // ===== 2. API REST - LISTE DES ARTICLES AVEC PAGINATION ET FILTRE =====
    
    /**
     * API REST - Liste des articles 
     */
    #[Route('/api/articles', name: 'api_articles_list', methods: ['GET'])]
    public function getArticlesApi(Request $request, DocumentManager $dm): JsonResponse
    {
        // 2a. Pagination : extraction des paramètres query string
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20; // Nombre d'articles par page fixe
        $offset = ($page - 1) * $limit;

        // 2b. Filtre par source (optionnel) : 'korben', 'dev.to', ou 'all'
        $source = $request->query->get('source', null);

        // 2c. Récupération de l'utilisateur connecté pour les statuts personnalisés
        $user = $this->getUser();
        $userId = (string) $user->getId();
        
        // 2d. Construction de la requête MongoDB avec QueryBuilder
        // Recherche uniquement les articles publics (userId = null)
        $qb = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null);
        
        // 2e. Application du filtre source si présent et différent de 'all'
        if ($source && $source !== 'all') {
            $qb->field('source')->equals($source);
        }
        
        // 2f. Tri par date de publication décroissante + pagination
        $qb->sort('publishedAt', 'DESC')
            ->limit($limit)
            ->skip($offset);

        // 2g. Exécution de la requête et conversion en tableau
        $articles = $qb->getQuery()->execute()->toArray();

        // 2h. Comptage total des articles (pour calculer le nombre de pages)
        // Requête identique mais avec count() au lieu de limit/skip
        $countQb = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null);
        
        if ($source && $source !== 'all') {
            $countQb->field('source')->equals($source);
        }
        
        $totalArticles = $countQb->count()
            ->getQuery()
            ->execute();

        // 2i. Calcul du nombre total de pages
        $totalPages = (int) ceil($totalArticles / $limit);
        
        // 2j. Transformation des documents MongoDB en JSON avec statuts personnalisés
        $data = $this->transformArticlesToJson($articles, $userId);

        // 2k. Réponse JSON avec données et métadonnées de pagination
        return $this->json([
            'articles' => $data,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalArticles' => $totalArticles,
                'articlesPerPage' => $limit,
            ]
        ]);
    }

    // ===== 3. API REST - LISTE DES SOURCES DISPONIBLES =====
    
    /**
     * API REST - Liste des sources disponibles
     */
    #[Route('/api/articles/sources', name: 'api_articles_sources', methods: ['GET'])]
    public function getSources(DocumentManager $dm): JsonResponse
    {
        // 3a. Requête MongoDB DISTINCT pour récupérer toutes les sources uniques
        // Filtre sur articles publics uniquement
        $sources = $dm->createQueryBuilder(Article::class)
            ->distinct('source')
            ->field('userId')->equals(null)
            ->getQuery()
            ->execute();  // PAS de ->toArray() ici
        
        // 3b. Conversion de l'itérateur MongoDB en tableau indexé
        $sources = is_array($sources) ? $sources : iterator_to_array($sources);

        // 3c. Réponse JSON avec tableau de sources (array_values pour réindexer)
        return $this->json([
            'sources' => array_values($sources)
        ]);
    }


    // ===== 4. API REST - RECHERCHE D'ARTICLES PAR MOT-CLÉ =====
    
    /**
     * API REST - Recherche d'articles
     */
    #[Route('/api/articles/search', name: 'api_articles_search', methods: ['GET'])]
    public function searchArticles(Request $request, DocumentManager $dm): JsonResponse
    {
        // 4a. Extraction et nettoyage du mot-clé de recherche
        $keyword = trim($request->query->get('q', ''));
        
        // 4b. Validation : retour immédiat si mot-clé vide
        if (empty($keyword)) {
            return $this->json([
                'articles' => [],
                'message' => 'Veuillez saisir un mot-cle'
            ]);
        }

        // 4c. Récupération de l'utilisateur pour les statuts personnalisés
        $user = $this->getUser();
        $userId = (string) $user->getId();

        // 4d. Construction de la requête MongoDB avec recherche REGEX case-insensitive
        // Recherche dans title, description et source avec opérateur OR
        $qb = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null)
            ->addOr(
                $dm->createQueryBuilder(Article::class)->expr()->field('title')->equals(new \MongoDB\BSON\Regex($keyword, 'i'))
            )
            ->addOr(
                $dm->createQueryBuilder(Article::class)->expr()->field('description')->equals(new \MongoDB\BSON\Regex($keyword, 'i'))
            )
            ->addOr(
                $dm->createQueryBuilder(Article::class)->expr()->field('source')->equals(new \MongoDB\BSON\Regex($keyword, 'i'))
            )
            ->sort('publishedAt', 'DESC')
            ->limit(50); // Limitation à 50 résultats pour la recherche

        // 4e. Exécution et transformation des résultats
        $articles = $qb->getQuery()->execute()->toArray();
        $data = $this->transformArticlesToJson($articles, $userId);

        // 4f. Réponse JSON avec résultats et métadonnées de recherche
        return $this->json([
            'articles' => $data,
            'keyword' => $keyword,
            'count' => count($data)
        ]);
    }

    // ===== 5. API REST - MARQUER COMME LU/NON LU (TOGGLE) =====
    
    /**
     * API REST - Marquer comme lu
     * Protection CSRF
     */
    #[Route('/api/articles/{id}/mark-read', name: 'api_articles_mark_read', methods: ['PATCH'])]
    public function markAsRead(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        // 5a. Récupération de l'utilisateur connecté
        $user = $this->getUser();
        $userId = (string) $user->getId();
        
        // 5b. Recherche de l'article par son ID MongoDB
        $article = $dm->getRepository(Article::class)->find($id);

        // 5c. Validation : article existant
        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        // 5d. Toggle du statut "lu" pour cet utilisateur (ajout/retrait dans readBy[])
        $article->toggleReadByUser($userId);
        $dm->flush();

        // 5e. Réponse JSON avec nouveau statut
        return $this->json([
            'success' => true,
            'isRead' => $article->isReadByUser($userId)
        ]);
    }

    // ===== 6. API REST - AJOUTER AUX FAVORIS =====
    
    /**
     * API REST - Ajouter aux favoris
     * Protection CSRF
     */
    #[Route('/api/articles/{id}/favorite', name: 'api_articles_add_favorite', methods: ['POST'])]
    public function addToFavorites(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        // 6a. Récupération de l'utilisateur connecté
        $user = $this->getUser();
        $userId = (string) $user->getId();
        
        // 6b. Recherche de l'article par son ID MongoDB
        $article = $dm->getRepository(Article::class)->find($id);

        // 6c. Validation : article existant
        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        // 6d. Ajout de l'utilisateur dans le tableau favoritedBy[]
        $article->addToFavorites($userId);
        $dm->flush();

        // 6e. Réponse JSON de succès
        return $this->json([
            'success' => true,
            'isFavorite' => true
        ]);
    }

    // ===== 7. API REST - RETIRER DES FAVORIS =====
    
    /**
     * API REST - Retirer des favoris
     *  Protection CSRF
     */
    #[Route('/api/articles/{id}/favorite', name: 'api_articles_remove_favorite', methods: ['DELETE'])]
    public function removeFromFavorites(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        // 7a. Récupération de l'utilisateur connecté
        $user = $this->getUser();
        $userId = (string) $user->getId();
        
        // 7b. Recherche de l'article par son ID MongoDB
        $article = $dm->getRepository(Article::class)->find($id);

        // 7c. Validation : article existant
        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        // 7d. Retrait de l'utilisateur du tableau favoritedBy[]
        $article->removeFromFavorites($userId);
        $dm->flush();

        // 7e. Réponse JSON de succès
        return $this->json([
            'success' => true,
            'isFavorite' => false
        ]);
    }

    // ===== 8. API REST - LISTE DES FAVORIS DE L'UTILISATEUR =====
    
    /**
     * API REST - Liste des favoris
     */
    #[Route('/api/articles/favorites', name: 'api_articles_favorites', methods: ['GET'])]
    public function getFavorites(DocumentManager $dm): JsonResponse
    {
        // 8a. Récupération de l'utilisateur connecté
        $user = $this->getUser();
        $userId = (string) $user->getId();

        // 8b. Requête MongoDB : articles publics où userId est dans favoritedBy[]
        $articles = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null)
            ->field('favoritedBy')->in([$userId])
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();

        // 8c. Transformation des résultats en JSON
        $data = $this->transformArticlesToJson($articles, $userId);

        // 8d. Réponse JSON avec favoris et compteur
        return $this->json([
            'favorites' => $data,
            'count' => count($data)
        ]);
    }

    // ===== 9. TRANSFORMATION MONGODB DOCUMENTS → JSON =====
    
    /**
     * Transforme les articles en JSON
     */
    private function transformArticlesToJson(array $articles, string $userId): array
    {
        // 9a. Initialisation du tableau de résultats
        $data = [];
        
        // 9b. Boucle sur chaque article MongoDB
        foreach ($articles as $article) {
            // 9c. Construction de l'objet JSON avec données de base et statuts personnalisés
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'url' => $article->getUrl(),
                'description' => $article->getDescription(),
                'source' => $article->getSource(),
                'publishedAt' => $article->getPublishedAt()?->format('d/m/Y H:i'),
                'isRead' => $article->isReadByUser($userId),        // Statut personnalisé
                'isFavorite' => $article->isFavoritedByUser($userId), // Statut personnalisé
            ];
        }
        
        // 9d. Retour du tableau JSON complet
        return $data;
    }
}