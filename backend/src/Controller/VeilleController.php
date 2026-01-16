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
        // Template simple que JavaScript va remplir dynamiquement
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
        $limit = 20;  // 20 articles par page
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

        // Compte total pour calculer le nombre de pages
        $totalArticles = $dm->createQueryBuilder(Article::class)
            ->field('userId')->equals((string) $user->getId())
            ->count()
            ->getQuery()
            ->execute();

        $totalPages = (int) ceil($totalArticles / $limit);

        // Transformation en tableau JSON
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
            ];
        }

        // Reponse JSON avec metadonnees de pagination
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
}
