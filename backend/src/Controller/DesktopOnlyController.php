<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DesktopOnlyController extends AbstractController
{
    #[Route('/desktop-only', name: 'app_desktop_only', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Toutes les fonctionnalités possibles
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

        // Ajouter Dashboard Admin UNIQUEMENT si l'utilisateur est admin
        if ($this->isGranted('ROLE_ADMIN')) {
            $allFeatures[] = [
                'name' => 'Dashboard Admin',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'reason' => 'Visualisation de statistiques globales en grille, métriques multiples nécessitant un grand écran'
            ];
        }

        return $this->render('desktop_only/index.html.twig', [
            'desktopFeatures' => $allFeatures
        ]);
    }
}