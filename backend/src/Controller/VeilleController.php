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

/**
 * Contrôleur pour la veille technologique (articles)
 * Protection CSRF gérée automatiquement par CsrfValidationSubscriber
 */
#[IsGranted('ROLE_USER')]
class VeilleController extends AbstractController
{
    /**
     * Page HTML - Interface Veille
     */
    #[Route('/veille', name: 'app_veille', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('veille/list.html.twig');
    }

    /**
     * API REST - Liste des articles avec pagination
     */
    #[Route('/api/articles', name: 'api_articles_list', methods: ['GET'])]
    public function getArticlesApi(Request $request, DocumentManager $dm): JsonResponse
    {
        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $user = $this->getUser();
        $userId = (string) $user->getId();
        
        $qb = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null)
            ->sort('publishedAt', 'DESC')
            ->limit($limit)
            ->skip($offset);

        $articles = $qb->getQuery()->execute()->toArray();

        $totalArticles = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null) 
            ->count()
            ->getQuery()
            ->execute();

        $totalPages = (int) ceil($totalArticles / $limit);
        $data = $this->transformArticlesToJson($articles, $userId);

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
     * API REST - Recherche d'articles
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
        $userId = (string) $user->getId();

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
            ->limit(50);

        $articles = $qb->getQuery()->execute()->toArray();
        $data = $this->transformArticlesToJson($articles, $userId);

        return $this->json([
            'articles' => $data,
            'keyword' => $keyword,
            'count' => count($data)
        ]);
    }

    /**
     * API REST - Marquer comme lu
     * Protection CSRF
     */
    #[Route('/api/articles/{id}/mark-read', name: 'api_articles_mark_read', methods: ['PATCH'])]
    public function markAsRead(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        $user = $this->getUser();
        $userId = (string) $user->getId();
        
        $article = $dm->getRepository(Article::class)->find($id);

        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        $article->toggleReadByUser($userId);
        $dm->flush();

        return $this->json([
            'success' => true,
            'isRead' => $article->isReadByUser($userId)
        ]);
    }

    /**
     * API REST - Ajouter aux favoris
     * Protection CSRF
     */
    #[Route('/api/articles/{id}/favorite', name: 'api_articles_add_favorite', methods: ['POST'])]
    public function addToFavorites(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        $user = $this->getUser();
        $userId = (string) $user->getId();
        
        $article = $dm->getRepository(Article::class)->find($id);

        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        $article->addToFavorites($userId);
        $dm->flush();

        return $this->json([
            'success' => true,
            'isFavorite' => true
        ]);
    }

    /**
     * API REST - Retirer des favoris
     *  Protection CSRF
     */
    #[Route('/api/articles/{id}/favorite', name: 'api_articles_remove_favorite', methods: ['DELETE'])]
    public function removeFromFavorites(string $id, Request $request, DocumentManager $dm): JsonResponse
    {
        $user = $this->getUser();
        $userId = (string) $user->getId();
        
        $article = $dm->getRepository(Article::class)->find($id);

        if (!$article) {
            return $this->json(['error' => 'Article non trouve'], 404);
        }

        $article->removeFromFavorites($userId);
        $dm->flush();

        return $this->json([
            'success' => true,
            'isFavorite' => false
        ]);
    }

    /**
     * API REST - Liste des favoris
     */
    #[Route('/api/articles/favorites', name: 'api_articles_favorites', methods: ['GET'])]
    public function getFavorites(DocumentManager $dm): JsonResponse
    {
        $user = $this->getUser();
        $userId = (string) $user->getId();

        $articles = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null)
            ->field('favoritedBy')->in([$userId])
            ->sort('publishedAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();

        $data = $this->transformArticlesToJson($articles, $userId);

        return $this->json([
            'favorites' => $data,
            'count' => count($data)
        ]);
    }

    /**
     * Transforme les articles en JSON
     */
    private function transformArticlesToJson(array $articles, string $userId): array
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
                'isRead' => $article->isReadByUser($userId),        
                'isFavorite' => $article->isFavoritedByUser($userId), 
            ];
        }
        return $data;
    }
}