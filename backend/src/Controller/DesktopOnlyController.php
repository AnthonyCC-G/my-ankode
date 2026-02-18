<?php

/**
 * DESKTOPONLYCONTROLLER.PHP - Page d'avertissement mobile
 * 
 * Responsabilités :
 * - Afficher une page explicative pour les utilisateurs mobiles
 * - Informer sur les fonctionnalités réservées au desktop
 * - Liste dynamique des features (Kanban, Snippets, Dashboard Admin)
 * - Inclusion conditionnelle du Dashboard Admin selon ROLE_ADMIN
 * 
 * Architecture :
 * - Redirection automatique depuis Kanban/Snippets si détection mobile
 * - Détection mobile double : côté serveur (User-Agent) + côté client (JS window.innerWidth < 768px)
 * - Bouton de retour vers la dernière page desktop visitée (via localStorage)
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DesktopOnlyController extends AbstractController
{
    // ===== 1. AFFICHAGE DE LA PAGE D'AVERTISSEMENT MOBILE =====
    
    #[Route('/desktop-only', name: 'app_desktop_only', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // 1a. Construction du tableau des fonctionnalités réservées au desktop
        // Chaque feature contient : nom, icône SVG path, raison technique
        $allFeatures = [
            [
                'name' => 'Kanban',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                'reason' => 'Drag & drop, organisation complexe de colonnes, édition rapide de tâches'
            ],
            [
                'name' => 'Snippets',
                'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                'reason' => 'Éditeur de code, coloration syntaxique, copier-coller efficace'
            ],
        ];

        // 1b. Ajout conditionnel du Dashboard Admin
        // Uniquement visible pour les utilisateurs ayant le rôle ROLE_ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            $allFeatures[] = [
                'name' => 'Dashboard Admin',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'reason' => 'Visualisation de statistiques globales en grille, métriques multiples nécessitant un grand écran'
            ];
        }

        // 1c. Rendu du template Twig avec la liste des fonctionnalités desktop-only
        return $this->render('desktop_only/index.html.twig', [
            'desktopFeatures' => $allFeatures
        ]);
    }
}