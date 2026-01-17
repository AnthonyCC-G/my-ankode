<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ODM\MongoDB\DocumentManager;


#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        DocumentManager $documentManager
    ): Response
    {
        $user = $this->getUser();
        
        // Repository MongoDB pour Articles
        $articleRepository = $documentManager->getRepository(\App\Document\Article::class);
        
        // STATS VEILLE
        $favoritesCount = $articleRepository->countFavoritesByUser($user);
        $readCount = $articleRepository->countReadByUser($user);
        
        return $this->render('dashboard/index.html.twig', [
            'favoritesCount' => $favoritesCount,
            'readCount' => $readCount,
        ]);
    }
}
