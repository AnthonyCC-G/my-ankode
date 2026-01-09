<?php

namespace App\Controller;

use App\Document\Snippet;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SnippetPageController extends AbstractController
{
    #[Route('/snippets', name: 'app_snippets', methods: ['GET'])]
    public function index(DocumentManager $dm): Response
    {
        $currentUser = $this->getUser();
        
        // Récupère uniquement les snippets de l'utilisateur connecté
        $snippets = $dm->getRepository(Snippet::class)
            ->findBy(
                ['userId' => $currentUser->getId()],
                ['createdAt' => 'DESC']  // Tri par date décroissante
            );

        return $this->render('snippet/list.html.twig', [
            'snippets' => $snippets,
        ]);
    }
}