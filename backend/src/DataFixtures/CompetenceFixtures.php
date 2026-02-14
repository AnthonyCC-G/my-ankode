<?php
// src/DataFixtures/CompetenceFixtures.php

namespace App\DataFixtures;

use App\Document\Snippet;
use App\Entity\Competence;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;  // AJOUTÉ

class CompetenceFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private DocumentManager $dm,
        private EntityManagerInterface $em  // AJOUTÉ
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

        // Récupérer le repository des projets
        $projectRepository = $this->em->getRepository(Project::class);

        // Récupérer les snippets MongoDB par userId
        $anthonySnippets = $this->getSnippetIdsByUserId((string) $anthony->getId());
        $aliceSnippets = $this->getSnippetIdsByUserId((string) $alice->getId());
        $bobSnippets = $this->getSnippetIdsByUserId((string) $bob->getId());
        $claraSnippets = $this->getSnippetIdsByUserId((string) $clara->getId());

        // ========================================
        // 1- COMPÉTENCES ANTHONY - Référentiel DWWM avec projets/snippets
        // ========================================
        
        $comp1 = new Competence();
        $comp1->setOwner($anthony);
        $comp1->setName('Symfony 7 & Doctrine ORM');
        $comp1->setDescription('Maîtrise complète de Symfony : controllers, services, DI, sécurité CSRF, Doctrine ORM avec requêtes optimisées. Objectif : architecture hexagonale + DDD pour projets complexes.');
        $comp1->addProject($projectRepository->findOneBy(['name' => 'MY-ANKODE - Refonte Admin Dashboard']));
        $comp1->addProject($projectRepository->findOneBy(['name' => 'MY-ANKODE - Module Compétences v2']));
        $comp1->setSnippetsIds([$anthonySnippets[0], $anthonySnippets[1], $anthonySnippets[2], $anthonySnippets[3]]);
        $comp1->setExternalProjects('Portfolio personnel, Blog familial, API gestion événements');
        $comp1->calculateLevel();
        $manager->persist($comp1);

        $comp2 = new Competence();
        $comp2->setOwner($anthony);
        $comp2->setName('PostgreSQL & Architecture BDD');
        $comp2->setDescription('Conception bases relationnelles : MCD/MLD/MPD, normalisation 3NF, index performants, transactions ACID. Objectif : maîtriser sharding + réplication pour scalabilité.');
        $comp2->addProject($projectRepository->findOneBy(['name' => 'MY-ANKODE - Refonte Admin Dashboard']));
        $comp2->setSnippetsIds([$anthonySnippets[1]]);
        $comp2->setExternalProjects('Migration MySQL → PostgreSQL ancien projet');
        $comp2->calculateLevel();
        $manager->persist($comp2);

        $comp3 = new Competence();
        $comp3->setOwner($anthony);
        $comp3->setName('JavaScript ES6+ & DOM Manipulation');
        $comp3->setDescription('JS moderne : async/await, modules, destructuring, manipulation DOM, gestion événements. Objectif : approfondir design patterns (Observer, Factory) et TypeScript.');
        $comp3->addProject($projectRepository->findOneBy(['name' => 'MY-ANKODE - Redesign Page Accueil']));
        $comp3->addProject($projectRepository->findOneBy(['name' => 'MY-ANKODE - Easter Eggs & Animations']));
        $comp3->setSnippetsIds([$anthonySnippets[4]]);
        $comp3->setExternalSnippets('Scripts automation workflow, Chrome extension perso');
        $comp3->calculateLevel();
        $manager->persist($comp3);

        $comp4 = new Competence();
        $comp4->setOwner($anthony);
        $comp4->setName('CSS Avancé & Responsive Design');
        $comp4->setDescription('Flexbox, Grid, animations, variables CSS, dark mode, mobile-first. Objectif : maîtriser CSS-in-JS (styled-components) et animations GSAP.');
        $comp4->addProject($projectRepository->findOneBy(['name' => 'MY-ANKODE - Redesign Page Accueil']));
        $comp4->setSnippetsIds([$anthonySnippets[5]]);
        $comp4->setExternalProjects('Redesign interface ancienne app');
        $comp4->calculateLevel();
        $manager->persist($comp4);

        $comp5 = new Competence();
        $comp5->setOwner($anthony);
        $comp5->setName('Docker & DevOps');
        $comp5->setDescription('Conteneurisation : Dockerfile multi-stage, docker-compose, volumes, networks. CI/CD avec GitHub Actions. Objectif : Kubernetes + monitoring (Prometheus/Grafana).');
        $comp5->addProject($projectRepository->findOneBy(['name' => 'MY-ANKODE - Refonte Admin Dashboard']));
        $comp5->setExternalProjects('Dockerisation app legacy client');
        $comp5->setExternalSnippets('Scripts deploy automatisé, backup BDD');
        $comp5->calculateLevel();
        $manager->persist($comp5);

        $comp6 = new Competence();
        $comp6->setOwner($anthony);
        $comp6->setName('Sécurité Web (OWASP Top 10)');
        $comp6->setDescription('Protection CSRF, XSS, injection SQL, authentification JWT, hashage bcrypt, HTTPS. Objectif : certification CEH (Certified Ethical Hacker) et pentesting avancé.');
        $comp6->setSnippetsIds([$anthonySnippets[3]]);
        $comp6->setExternalProjects('Audit sécurité app e-commerce');
        $comp6->calculateLevel();
        $manager->persist($comp6);

        // ========================================
        // 2- COMPÉTENCES ALICE - Frontend avec projets concrets
        // ========================================

        $comp7 = new Competence();
        $comp7->setOwner($alice);
        $comp7->setName('HTML/CSS Sémantique');
        $comp7->setDescription('HTML5 structuré (header/nav/main/footer), accessibilité WCAG AA, SEO on-page. Objectif : maîtriser WAI-ARIA et obtenir certification a11y (accessibility).');
        $comp7->addProject($projectRepository->findOneBy(['name' => 'Intégration Maquette Figma - Agence Voyage']));
        $comp7->addProject($projectRepository->findOneBy(['name' => 'Portfolio Personnel v3']));
        $comp7->setSnippetsIds([$aliceSnippets[0], $aliceSnippets[1]]);
        $comp7->setExternalProjects('Refonte site association locale');
        $comp7->calculateLevel();
        $manager->persist($comp7);

        $comp8 = new Competence();
        $comp8->setOwner($alice);
        $comp8->setName('Responsive Design & Mobile-First');
        $comp8->setDescription('Media queries, breakpoints, Flexbox/Grid, touch events mobile. Maîtrise approche mobile-first. Objectif : Progressive Web Apps (PWA) avec offline support.');
        $comp8->addProject($projectRepository->findOneBy(['name' => 'Intégration Maquette Figma - Agence Voyage']));
        $comp8->addProject($projectRepository->findOneBy(['name' => 'TP Cinema - Responsive Design']));
        $comp8->setSnippetsIds([$aliceSnippets[5]]);
        $comp8->setExternalProjects('App météo responsive, Landing page startup');
        $comp8->calculateLevel();
        $manager->persist($comp8);

        $comp9 = new Competence();
        $comp9->setOwner($alice);
        $comp9->setName('JavaScript Moderne & API Fetch');
        $comp9->setDescription('ES6+, promises, async/await, gestion erreurs, manipulation JSON. Objectif : approfondir WebSockets temps réel et Service Workers.');
        $comp9->addProject($projectRepository->findOneBy(['name' => 'Clone Netflix - Interface uniquement']));
        $comp9->setSnippetsIds([$aliceSnippets[2], $aliceSnippets[4]]);
        $comp9->setExternalSnippets('Script scraping données publiques, Bot Discord');
        $comp9->calculateLevel();
        $manager->persist($comp9);

        $comp10 = new Competence();
        $comp10->setOwner($alice);
        $comp10->setName('Animations & Micro-interactions');
        $comp10->setDescription('Transitions CSS, keyframes, performance GPU (transform/opacity), UX subtile. Objectif : animations complexes avec Framer Motion et Three.js 3D.');
        $comp10->addProject($projectRepository->findOneBy(['name' => 'Portfolio Personnel v3']));
        $comp10->setSnippetsIds([$aliceSnippets[3]]);
        $comp10->setExternalProjects('Page présentation produit avec parallax');
        $comp10->calculateLevel();
        $manager->persist($comp10);

        $comp11 = new Competence();
        $comp11->setOwner($alice);
        $comp11->setName('UI/UX Design & Figma');
        $comp11->setDescription('Wireframes, mockups, prototypes interactifs, design systems, Auto Layout. Objectif : master UX research (tests utilisateurs, A/B testing).');
        $comp11->addProject($projectRepository->findOneBy(['name' => 'Intégration Maquette Figma - Agence Voyage']));
        $comp11->setExternalProjects('Redesign dashboard analytics client');
        $comp11->setExternalSnippets('Composants Figma réutilisables');
        $comp11->calculateLevel();
        $manager->persist($comp11);

        // ========================================
        // 3- COMPÉTENCES BOB - Backend avec vrais projets
        // ========================================

        $comp12 = new Competence();
        $comp12->setOwner($bob);
        $comp12->setName('PHP 8.3 & POO Avancée');
        $comp12->setDescription('Typage strict, attributs, enums, traits, interfaces, design patterns (Factory, Repository, Strategy). Objectif : maîtriser architecture Clean Code + SOLID.');
        $comp12->addProject($projectRepository->findOneBy(['name' => 'API REST - Bibliothèque Municipale']));
        $comp12->addProject($projectRepository->findOneBy(['name' => 'Todo List API (oui encore une)']));
        $comp12->setSnippetsIds([$bobSnippets[2], $bobSnippets[5]]);
        $comp12->setExternalProjects('Refactoring legacy code client');
        $comp12->calculateLevel();
        $manager->persist($comp12);

        $comp13 = new Competence();
        $comp13->setOwner($bob);
        $comp13->setName('API REST & Architecture');
        $comp13->setDescription('Design RESTful, versioning, HATEOAS, documentation OpenAPI, pagination, filtres. Objectif : GraphQL et architecture microservices.');
        $comp13->addProject($projectRepository->findOneBy(['name' => 'API REST - Bibliothèque Municipale']));
        $comp13->addProject($projectRepository->findOneBy(['name' => 'Todo List API (oui encore une)']));
        $comp13->setExternalProjects('API mobile app gestion tâches');
        $comp13->calculateLevel();
        $manager->persist($comp13);

        $comp14 = new Competence();
        $comp14->setOwner($bob);
        $comp14->setName('Authentification & Sécurité');
        $comp14->setDescription('JWT, refresh tokens, OAuth2, bcrypt, sessions sécurisées, CORS. Objectif : implémenter SSO (Single Sign-On) et 2FA (authentification double facteur).');
        $comp14->addProject($projectRepository->findOneBy(['name' => 'Système Auth JWT from scratch']));
        $comp14->setSnippetsIds([$bobSnippets[1]]);
        $comp14->setExternalProjects('Système auth centralisé multi-apps');
        $comp14->calculateLevel();
        $manager->persist($comp14);

        $comp15 = new Competence();
        $comp15->setOwner($bob);
        $comp15->setName('Doctrine ORM & Optimisation');
        $comp15->setDescription('Entities, relations, migrations, requêtes DQL/QueryBuilder, résolution N+1. Objectif : Event Sourcing et CQRS (Command Query Responsibility Segregation).');
        $comp15->addProject($projectRepository->findOneBy(['name' => 'API REST - Bibliothèque Municipale']));
        $comp15->setSnippetsIds([$bobSnippets[0]]);
        $comp15->setExternalSnippets('Requêtes complexes analytics');
        $comp15->calculateLevel();
        $manager->persist($comp15);

        $comp16 = new Competence();
        $comp16->setOwner($bob);
        $comp16->setName('Tests Automatisés (TDD)');
        $comp16->setDescription('PHPUnit, tests unitaires/intégration, mocks, coverage, CI/CD. Objectif : TDD strict (tests avant code) et tests end-to-end avec Symfony Panther.');
        $comp16->addProject($projectRepository->findOneBy(['name' => 'Todo List API (oui encore une)']));
        $comp16->setSnippetsIds([$bobSnippets[4]]);
        $comp16->setExternalProjects('Suite tests API complète');
        $comp16->calculateLevel();
        $manager->persist($comp16);

        $comp17 = new Competence();
        $comp17->setOwner($bob);
        $comp17->setName('Docker & Infrastructure');
        $comp17->setDescription('Conteneurisation, orchestration, multi-stage builds, optimisation images. Objectif : Kubernetes production-ready et monitoring distribué.');
        $comp17->setSnippetsIds([$bobSnippets[3]]);
        $comp17->setExternalProjects('Migration infra vers conteneurs');
        $comp17->setExternalSnippets('Scripts maintenance containers');
        $comp17->calculateLevel();
        $manager->persist($comp17);

        // ========================================
        // 4- COMPÉTENCES CLARA - Progression débutante réaliste
        // ========================================

        $comp18 = new Competence();
        $comp18->setOwner($clara);
        $comp18->setName('HTML - Structure de base');
        $comp18->setDescription('Balises essentielles, sémantique simple, formulaires. Objectif : comprendre accessibilité et SEO pour créer des pages bien structurées.');
        $comp18->addProject($projectRepository->findOneBy(['name' => 'Site Vitrine - Cours de Maths']));
        $comp18->setSnippetsIds([$claraSnippets[4]]);
        $comp18->calculateLevel();
        $manager->persist($comp18);

        $comp19 = new Competence();
        $comp19->setOwner($clara);
        $comp19->setName('CSS - Mise en forme');
        $comp19->setDescription('Sélecteurs, box model, Flexbox basique, couleurs. Objectif : maîtriser Grid et créer des layouts complexes sans framework.');
        $comp19->addProject($projectRepository->findOneBy(['name' => 'Calculatrice Web (HTML/CSS/JS)']));
        $comp19->addProject($projectRepository->findOneBy(['name' => 'Site Vitrine - Cours de Maths']));
        $comp19->setSnippetsIds([$claraSnippets[3]]);
        $comp19->calculateLevel();
        $manager->persist($comp19);

        $comp20 = new Competence();
        $comp20->setOwner($clara);
        $comp20->setName('JavaScript - Bases');
        $comp20->setDescription('Variables, fonctions, conditions, boucles, événements simples. Objectif : créer des applications interactives complètes sans copier-coller StackOverflow !');
        $comp20->addProject($projectRepository->findOneBy(['name' => 'Calculatrice Web (HTML/CSS/JS)']));
        $comp20->addProject($projectRepository->findOneBy(['name' => 'Convertisseur Unités Mathématiques']));
        $comp20->setSnippetsIds([$claraSnippets[0], $claraSnippets[1], $claraSnippets[2]]);
        $comp20->calculateLevel();
        $manager->persist($comp20);

        $comp21 = new Competence();
        $comp21->setOwner($clara);
        $comp21->setName('Git - Versionning');
        $comp21->setDescription('Commandes de base (add, commit, push), compréhension des branches. Objectif : maîtriser Git Flow et résoudre les conflits sans panique.');
        $comp21->setExternalProjects('Tous mes projets de formation versionnés');
        $comp21->calculateLevel();
        $manager->persist($comp21);

        $comp22 = new Competence();
        $comp22->setOwner($clara);
        $comp22->setName('Logique de programmation');
        $comp22->setDescription('Algorithmique basique, décomposition problèmes, pseudocode. Objectif : résoudre challenges HackerRank niveau intermédiaire et participer à des hackathons.');
        $comp22->addProject($projectRepository->findOneBy(['name' => 'Convertisseur Unités Mathématiques']));
        $comp22->setExternalProjects('Exercices daily sur Codewars');
        $comp22->calculateLevel();
        $manager->persist($comp22);

        $manager->flush();
        
        echo "\n 22 compétences créées avec calcul automatique des niveaux !\n";
        echo " Répartition : Anthony (6), Alice (5), Bob (6), Clara (5)\n";
        echo " Compétences liées aux projets Kanban et snippets MongoDB\n";
    }

    /**
     * Récupérer les IDs des snippets MongoDB pour un userId donné
     */
    private function getSnippetIdsByUserId(string $userId): array
    {
        $snippets = $this->dm->getRepository(Snippet::class)->findBy(['userId' => $userId]);
        return array_map(fn($snippet) => $snippet->getId(), $snippets);
    }

    public static function getGroups(): array
    {
        return ['competence'];
    }
}