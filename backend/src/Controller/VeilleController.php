<?php

namespace App\Controller;

use App\Document\Article;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class VeilleController extends AbstractController
    {
        #[Route('/veille', name: 'app_veille', methods: ['GET'])]
        public function index(DocumentManager $dm): Response
        {
            // Récupère les 50 derniers articles triés par date de publication
            $articles = $dm->getRepository(Article::class)
                ->findBy(
                    [],                              // Pas de filtre
                    ['publishedAt' => 'DESC'],       // Tri par date décroissante
                    50                                // Limite à 50
                );

            return $this->render('veille/list.html.twig', [
                'articles' => $articles,
            ]);
        }
    }