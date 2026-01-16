<?php

namespace App\Controller;

use App\Document\Article;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class VeilleController extends AbstractController
{
    /**
     * Page HTML - Interface Veille (template que JavaScript va remplir)
     */
    #[Route('/veille', name: 'app_veille', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('veille/list.html.twig');
    }

    /**
     * API REST - Liste des articles avec pagination
     * GET /api/articles?page=1
     */
    #[Route('/api/articles', name: 'api_articles_list', methods: ['GET'])]
    public function getArticlesApi(Request $request, DocumentManager $dm): JsonResponse
    {
        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Recupere l'utilisateur connecte
        $user = $this->getUser();
        
        // Requete MongoDB avec pagination
        $qb = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals((string) $user->getId())
            ->sort('publishedAt', 'DESC')
            ->limit($limit)
            ->skip($offset);

        $articles = $qb->getQuery()->execute()->toArray();

        // Compte total
        $totalArticles = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals((string) $user->getId())
            ->count()
            ->getQuery()
            ->execute();

        $totalPages = (int) ceil($totalArticles / $limit);

        // Transformation en JSON
        $data = $this->transformArticlesToJson($articles);

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

    /**
     * API REST - Recherche d'articles par mot-cle
     * GET /api/articles/search?q=react
     */
    #[Route('/api/articles/search', name: 'api_articles_search', methods: ['GET'])]
    public function searchArticles(Request $request, DocumentManager $dm): JsonResponse
    {
        $keyword = trim($request->query->get('q', ''));
        
        if (empty($keyword)) {
            return $this->json([
                'articles' => [],
                'message' => 'Veuillez saisir un mot-cle'
            ]);
        }

        $user = $this->getUser();

        // Recherche MongoDB avec regex (insensible à la casse)
        $qb = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals((string) $user->getId())
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
            ->limit(50); // Max 50 resultats

        $articles = $qb->getQuery()->execute()->toArray();
        $data = $this->transformArticlesToJson($articles);

        return $this->json([
            'articles' => $data,
            'keyword' => $keyword,
            'count' => count($data)
        ]);
    }

    /**
     * API REST - Marquer un article comme lu
     * PATCH /api/articles/{id}/mark-read
     */
    #[Route('/api/articles/{id}/mark-read', name: 'api_articles_mark_read', methods: ['PATCH'])]
    public function markAsRead(string $id, DocumentManager $dm): JsonResponse
    {
        $user = $this->getUser();
        
        // Recupere l'article
        $article = $dm->getRepository(Article::class)->find($id);

        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        // Verifie ownership
        if ($article->getUserId() !== (string) $user->getId()) {
            return $this->json(['error' => 'Acces refuse'], 403);
        }

        // Toggle isRead
        $article->setIsRead(!$article->isRead());
        $dm->flush();

        return $this->json([
            'success' => true,
            'isRead' => $article->isRead()
        ]);
    }

    /**
     * API REST - Ajouter un article aux favoris
     * POST /api/articles/{id}/favorite
     */
    #[Route('/api/articles/{id}/favorite', name: 'api_articles_add_favorite', methods: ['POST'])]
    public function addToFavorites(string $id, DocumentManager $dm): JsonResponse
    {
        $user = $this->getUser();
        
        $article = $dm->getRepository(Article::class)->find($id);

        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        // Verifie ownership
        if ($article->getUserId() !== (string) $user->getId()) {
            return $this->json(['error' => 'Acces refuse'], 403);
        }

        $article->setIsFavorite(true);
        $dm->flush();

        return $this->json([
            'success' => true,
            'isFavorite' => true
        ]);
    }

    /**
     * API REST - Retirer un article des favoris
     * DELETE /api/articles/{id}/favorite
     */
    #[Route('/api/articles/{id}/favorite', name: 'api_articles_remove_favorite', methods: ['DELETE'])]
    public function removeFromFavorites(string $id, DocumentManager $dm): JsonResponse
    {
        $user = $this->getUser();
        
        $article = $dm->getRepository(Article::class)->find($id);

        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        // Verifie ownership
        if ($article->getUserId() !== (string) $user->getId()) {
            return $this->json(['error' => 'Acces refuse'], 403);
        }

        $article->setIsFavorite(false);
        $dm->flush();

        return $this->json([
            'success' => true,
            'isFavorite' => false
        ]);
    }

    /**
     * API REST - Liste des articles favoris
     * GET /api/articles/favorites
     */
    #[Route('/api/articles/favorites', name: 'api_articles_favorites', methods: ['GET'])]
    public function getFavorites(DocumentManager $dm): JsonResponse
    {
        $user = $this->getUser();

        // Recupere les favoris
        $articles = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals((string) $user->getId())
            ->field('isFavorite')->equals(true)
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();

        $data = $this->transformArticlesToJson($articles);

        return $this->json([
            'favorites' => $data,
            'count' => count($data)
        ]);
    }

    /**
     * Transforme les articles en tableau JSON
     */
    private function transformArticlesToJson(array $articles): array
    {
        $data = [];
        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'url' => $article->getUrl(),
                'description' => $article->getDescription(),
                'source' => $article->getSource(),
                'publishedAt' => $article->getPublishedAt()?->format('d/m/Y H:i'),
                'isRead' => $article->isRead(),
                'isFavorite' => $article->isFavorite(),
            ];
        }
        return $data;
    }
}

