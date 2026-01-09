<?php

namespace App\Controller;

use App\Repository\CompetenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CompetencePageController extends AbstractController
{
    #[Route('/competences', name: 'app_competences', methods: ['GET'])]
    public function index(CompetenceRepository $competenceRepository): Response
    {
        // Récupère uniquement les compétences de l'utilisateur connecté
        $competences = $competenceRepository->findBy(
            ['owner' => $this->getUser()],
            ['name' => 'ASC']  // Tri alphabétique par nom
        );

        return $this->render('competence/list.html.twig', [
            'competences' => $competences,
        ]);
    }
}