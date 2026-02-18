<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller pour la page HTML Competences
 * Le JavaScript chargera dynamiquement les competences via l'API /api/competences
 */
#[IsGranted('ROLE_USER')]
class CompetencePageController extends AbstractController
{
    #[Route('/competences', name: 'app_competences', methods: ['GET'])]
    public function index(): Response
    {
        // Rendu du template vide (comme Kanban/Snippets/Veille)
        // Le JavaScript chargera les donnees via l'API
        return $this->render('competence/list.html.twig', [
            'pageTitle' => 'Gestion des Competences'
        ]);
    }
}
