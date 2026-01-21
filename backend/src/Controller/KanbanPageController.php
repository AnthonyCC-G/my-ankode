<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class KanbanPageController extends AbstractController
{
    #[Route('/kanban', name: 'app_kanban', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // ===== DÉTECTION MOBILE CÔTÉ SERVEUR =====
        // Double sécurité avec le JavaScript 
        $userAgent = $request->headers->get('User-Agent');
        
        // Détection simple : si contient "Mobile" ou "Android" ou "iPhone"
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
            // Redirection vers la page desktop-only
            return $this->redirectToRoute('app_desktop_only');
        }
        
        // ===== RENDU DU TEMPLATE VIDE =====
        // Le JavaScript chargera dynamiquement les projets et tâches via l'API
        return $this->render('kanban/list.html.twig', [
            // Pas de données passées : tout sera chargé en JS via /api/projects
            'pageTitle' => 'Kanban - Gestion de projets'
        ]);
    }
}