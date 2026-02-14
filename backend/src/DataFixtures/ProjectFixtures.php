<?php
// src/DataFixtures/ProjectFixtures.php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;  // AJOUTÉ

class ProjectFixtures extends Fixture implements FixtureGroupInterface
{
    // CONSTRUCTEUR AJOUTÉ
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Récupérer les users DIRECTEMENT depuis PostgreSQL
        $userRepository = $this->em->getRepository(User::class);
        $anthony = $userRepository->findOneBy(['username' => 'anthony_dev']);
        $alice = $userRepository->findOneBy(['username' => 'alice_codes']);
        $bob = $userRepository->findOneBy(['username' => 'bob_debug']);
        $clara = $userRepository->findOneBy(['username' => 'clara_learns']);

        // Vérifier que les users existent
        if (!$anthony || !$alice || !$bob || !$clara) {
            throw new \Exception('Users not found! Load UserFixtures first.');
        }

        // ========================================
        // 1- PROJETS ANTHONY (Admin) - Roadmap MY-ANKODE
        // ========================================
        $projectsAnthony = [
            [
                'name' => 'MY-ANKODE - Refonte Admin Dashboard',
                'description' => 'Amélioration interface admin : gestion utilisateurs, modération contenu, statistiques avancées avec graphiques temps réel',
                'created_offset' => '-5 months'
            ],
            [
                'name' => 'MY-ANKODE - Module Compétences v2',
                'description' => 'Refonte système de tracking compétences : graphiques de progression, suggestions personnalisées sur les articles lu, tag des snippets, ...',
                'created_offset' => '-4 months'
            ],
            [
                'name' => 'MY-ANKODE - Redesign Page Accueil',
                'description' => 'Nouvelle identité visuelle : hero section dynamique, animations subtiles, amélioration du dark mode avec transitions fluides',
                'created_offset' => '-3 months'
            ],
            [
                'name' => 'MY-ANKODE - Easter Eggs & Animations',
                'description' => 'Système d\'animations légères (micro-interactions, transitions CSS) + easter eggs cachés pour devs (Konami code, etc.)',
                'created_offset' => '-2 months'
            ]
        ];

        foreach ($projectsAnthony as $index => $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setOwner($anthony);
            $project->setCreatedAt(new \DateTime($data['created_offset']));

            $manager->persist($project);
            $this->addReference('project_anthony_' . $index, $project);
        }

        // ========================================
        // 2- PROJETS ALICE (Frontend) - Apprentissage interfaces
        // ========================================
        $projectsAlice = [
            [
                'name' => 'Intégration Maquette Figma - Agence Voyage',
                'description' => 'Reproduction pixel-perfect d\'une maquette Figma : responsive design, animations CSS, accessibilité WCAG AA',
                'created_offset' => '-2 months 15 days'
            ],
            [
                'name' => 'Clone Netflix - Interface uniquement',
                'description' => 'Reproduction interface Netflix : carrousels horizontaux, hover effects sur vignettes, modal vidéo (sans backend)',
                'created_offset' => '-2 months'
            ],
            [
                'name' => 'Portfolio Personnel v3',
                'description' => 'Troisième version de mon portfolio (jamais satisfaite !) : animations GSAP, dark/light mode, section projets interactive',
                'created_offset' => '-1 month 20 days'
            ],
            [
                'name' => 'TP Cinema - Responsive Design',
                'description' => 'Refonte responsive du TP Cinema : approche mobile-first, breakpoints optimisés, menu hamburger accessible',
                'created_offset' => '-1 month'
            ]
        ];

        foreach ($projectsAlice as $index => $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setOwner($alice);
            $project->setCreatedAt(new \DateTime($data['created_offset']));

            $manager->persist($project);
            $this->addReference('project_alice_' . $index, $project);
        }

        // ========================================
        // 3- PROJETS BOB (Backend) - Apprentissage API/BDD
        // ========================================
        $projectsBob = [
            [
                'name' => 'Calculatrice Console PHP',
                'description' => 'Mon premier projet de formation : calculatrice en ligne de commande avec opérations basiques et gestion des erreurs',
                'created_offset' => '-1 month 25 days'
            ],
            [
                'name' => 'API REST - Bibliothèque Municipale',
                'description' => 'CRUD complet : gestion livres, auteurs, emprunts. Stack : PostgreSQL + Symfony + authentification JWT',
                'created_offset' => '-1 month 15 days'
            ],
            [
                'name' => 'Todo List API (oui encore une)',
                'description' => 'Énième API REST todo (classique formation) : users, projets, tâches. Avec tests PHPUnit et documentation OpenAPI',
                'created_offset' => '-1 month 5 days'
            ],
            [
                'name' => 'Système Auth JWT from scratch',
                'description' => 'Comprendre JWT en profondeur : génération tokens, refresh tokens, middleware de validation, système de blacklist',
                'created_offset' => '-3 weeks'
            ]
        ];

        foreach ($projectsBob as $index => $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setOwner($bob);
            $project->setCreatedAt(new \DateTime($data['created_offset']));

            $manager->persist($project);
            $this->addReference('project_bob_' . $index, $project);
        }

        // ========================================
        // 4- PROJETS CLARA (Reconversion) - Projets pédagogiques
        // ========================================
        $projectsClara = [
            [
                'name' => 'Calculatrice Web (HTML/CSS/JS)',
                'description' => 'Ma toute première application web ! Calculatrice simple avec interface graphique. Fière du résultat même si le code est perfectible',
                'created_offset' => '-3 weeks'
            ],
            [
                'name' => 'Site Vitrine - Cours de Maths',
                'description' => 'Site statique pour mes anciens élèves : cours en ligne, exercices corrigés, formulaire de contact (nostalgie de l\'enseignement)',
                'created_offset' => '-2 weeks 3 days'
            ],
            [
                'name' => 'Convertisseur Unités Mathématiques',
                'description' => 'Convertisseur longueurs/surfaces/volumes : alliance parfaite entre mes compétences en maths et ma nouvelle passion pour le dev !',
                'created_offset' => '-1 week 5 days'
            ]
        ];

        foreach ($projectsClara as $index => $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setOwner($clara);
            $project->setCreatedAt(new \DateTime($data['created_offset']));

            $manager->persist($project);
            $this->addReference('project_clara_' . $index, $project);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['project'];
    }
}