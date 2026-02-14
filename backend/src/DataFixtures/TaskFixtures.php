<?php
// src/DataFixtures/TaskFixtures.php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\Project;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;  // AJOUTÉ

class TaskFixtures extends Fixture implements FixtureGroupInterface
{
    // CONSTRUCTEUR AJOUTÉ
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ========================================
        // 1- TÂCHES ANTHONY - Roadmap MY-ANKODE
        // ========================================
        
        // Projet 0 : Refonte Admin Dashboard
        $tasksAnthony0 = [
            ['title' => 'Créer page admin/users avec DataTables', 'status' => 'done', 'created_offset' => '-5 months'],
            ['title' => 'Implémenter modération contenu (snippets/articles)', 'status' => 'done', 'created_offset' => '-4 months 20 days'],
            ['title' => 'Ajouter graphiques statistiques (Chart.js)', 'status' => 'in_progress', 'created_offset' => '-4 months 10 days'],
            ['title' => 'Système de logs admin (qui a fait quoi)', 'status' => 'todo', 'created_offset' => '-4 months 5 days'],
            ['title' => 'Export CSV des utilisateurs', 'status' => 'todo', 'created_offset' => '-4 months'],
        ];
        $this->createTasksForProject('MY-ANKODE - Refonte Admin Dashboard', $tasksAnthony0, $manager);

        // Projet 1 : Module Compétences v2
        $tasksAnthony1 = [
            ['title' => 'Refactorer système de tracking (niveau 1-5)', 'status' => 'done', 'created_offset' => '-4 months'],
            ['title' => 'Créer graphiques de progression (radar chart)', 'status' => 'in_progress', 'created_offset' => '-3 months 15 days'],
            ['title' => 'Suggestions auto basées sur référentiel DWWM', 'status' => 'in_progress', 'created_offset' => '-3 months 10 days'],
            ['title' => 'Lier compétences aux projets/snippets', 'status' => 'todo', 'created_offset' => '-3 months 5 days'],
        ];
        $this->createTasksForProject('MY-ANKODE - Module Compétences v2', $tasksAnthony1, $manager);

        // Projet 2 : Redesign Page Accueil
        $tasksAnthony2 = [
            ['title' => 'Refonte hero section (animations GSAP)', 'status' => 'done', 'created_offset' => '-3 months'],
            ['title' => 'Améliorer transitions dark/light mode', 'status' => 'in_progress', 'created_offset' => '-2 months 20 days'],
            ['title' => 'Optimiser performances (Lighthouse > 90)', 'status' => 'in_progress', 'created_offset' => '-2 months 15 days'],
            ['title' => 'A/B testing nouveau design', 'status' => 'todo', 'created_offset' => '-2 months 10 days'],
        ];
        $this->createTasksForProject('MY-ANKODE - Redesign Page Accueil', $tasksAnthony2, $manager);

        // Projet 3 : Easter Eggs & Animations
        $tasksAnthony3 = [
            ['title' => 'Implémenter Konami Code (↑↑↓↓←→←→BA)', 'status' => 'done', 'created_offset' => '-2 months'],
            ['title' => 'Micro-interactions sur boutons (hover/click)', 'status' => 'in_progress', 'created_offset' => '-1 month 20 days'],
            ['title' => 'Easter egg : mode "Matrix" (pluie de code)', 'status' => 'todo', 'created_offset' => '-1 month 15 days'],
            ['title' => 'Animations de chargement personnalisées', 'status' => 'todo', 'created_offset' => '-1 month 10 days'],
        ];
        $this->createTasksForProject('MY-ANKODE - Easter Eggs & Animations', $tasksAnthony3, $manager);

        // ========================================
        // 2- TÂCHES ALICE - Frontend
        // ========================================

        // Projet 0 : Intégration Maquette Figma
        $tasksAlice0 = [
            ['title' => 'Analyser maquette et découper composants', 'status' => 'done', 'created_offset' => '-2 months 15 days'],
            ['title' => 'Intégration HTML/CSS de la homepage', 'status' => 'done', 'created_offset' => '-2 months 10 days'],
            ['title' => 'Responsive mobile (breakpoint 768px)', 'status' => 'in_progress', 'created_offset' => '-2 months 5 days'],
            ['title' => 'Corriger ce bug de centrage vertical (encore...)', 'status' => 'in_progress', 'created_offset' => '-2 months 2 days'],
            ['title' => 'Tests accessibilité WCAG AA', 'status' => 'todo', 'created_offset' => '-2 months'],
        ];
        $this->createTasksForProject('Intégration Maquette Figma - Agence Voyage', $tasksAlice0, $manager);

        // Projet 1 : Clone Netflix
        $tasksAlice1 = [
            ['title' => 'Créer carrousel horizontal (swiper.js)', 'status' => 'done', 'created_offset' => '-2 months'],
            ['title' => 'Hover effect sur vignettes (scale + shadow)', 'status' => 'done', 'created_offset' => '-1 month 25 days'],
            ['title' => 'Modal vidéo avec player custom', 'status' => 'in_progress', 'created_offset' => '-1 month 20 days'],
            ['title' => 'Pourquoi le z-index ne fonctionne pas ?!', 'status' => 'todo', 'created_offset' => '-1 month 18 days'],
            ['title' => 'Lazy loading des images', 'status' => 'todo', 'created_offset' => '-1 month 15 days'],
        ];
        $this->createTasksForProject('Clone Netflix - Interface uniquement', $tasksAlice1, $manager);

        // Projet 2 : Portfolio v3
        $tasksAlice2 = [
            ['title' => 'Refonte complète (encore) du design', 'status' => 'done', 'created_offset' => '-1 month 20 days'],
            ['title' => 'Animations GSAP sur scroll', 'status' => 'in_progress', 'created_offset' => '-1 month 15 days'],
            ['title' => 'Dark mode avec CSS variables', 'status' => 'in_progress', 'created_offset' => '-1 month 10 days'],
            ['title' => 'Section projets interactive (filtres)', 'status' => 'todo', 'created_offset' => '-1 month 5 days'],
            ['title' => 'Formulaire de contact (EmailJS)', 'status' => 'todo', 'created_offset' => '-1 month'],
        ];
        $this->createTasksForProject('Portfolio Personnel v3', $tasksAlice2, $manager);

        // Projet 3 : TP Cinema Responsive
        $tasksAlice3 = [
            ['title' => 'Mobile-first : layout smartphone', 'status' => 'done', 'created_offset' => '-1 month'],
            ['title' => 'Menu hamburger accessible (ARIA)', 'status' => 'in_progress', 'created_offset' => '-3 weeks'],
            ['title' => 'Grid layout tablette (768-1024px)', 'status' => 'in_progress', 'created_offset' => '-2 weeks 5 days'],
            ['title' => 'Tests cross-browser (Safari bug...)', 'status' => 'todo', 'created_offset' => '-2 weeks'],
        ];
        $this->createTasksForProject('TP Cinema - Responsive Design', $tasksAlice3, $manager);

        // ========================================
        // 3- TÂCHES BOB - Backend
        // ========================================

        // Projet 0 : Calculatrice Console
        $tasksBob0 = [
            ['title' => 'Implémenter opérations de base (+, -, *, /)', 'status' => 'done', 'created_offset' => '-1 month 25 days'],
            ['title' => 'Gestion erreurs (division par zéro)', 'status' => 'done', 'created_offset' => '-1 month 23 days'],
            ['title' => 'Ajouter opérations avancées (sqrt, pow)', 'status' => 'done', 'created_offset' => '-1 month 20 days'],
            ['title' => 'Refactoring : PSR-12 + PHPStan level 5', 'status' => 'in_progress', 'created_offset' => '-1 month 18 days'],
        ];
        $this->createTasksForProject('Calculatrice Console PHP', $tasksBob0, $manager);

        // Projet 1 : API Bibliothèque
        $tasksBob1 = [
            ['title' => 'Schéma BDD (livres, auteurs, emprunts)', 'status' => 'done', 'created_offset' => '-1 month 15 days'],
            ['title' => 'CRUD Livres (API REST)', 'status' => 'done', 'created_offset' => '-1 month 10 days'],
            ['title' => 'Authentification JWT', 'status' => 'in_progress', 'created_offset' => '-1 month 5 days'],
            ['title' => 'Débugger requête N+1 sur /api/books (Doctrine)', 'status' => 'in_progress', 'created_offset' => '-1 month 2 days'],
            ['title' => 'Tests PHPUnit (coverage > 80%)', 'status' => 'todo', 'created_offset' => '-1 month'],
        ];
        $this->createTasksForProject('API REST - Bibliothèque Municipale', $tasksBob1, $manager);

        // Projet 2 : Todo List API
        $tasksBob2 = [
            ['title' => 'Entités User/Project/Task + relations', 'status' => 'done', 'created_offset' => '-1 month 5 days'],
            ['title' => 'Endpoints CRUD basiques', 'status' => 'done', 'created_offset' => '-1 month'],
            ['title' => 'Validation + gestion erreurs 400/404/500', 'status' => 'in_progress', 'created_offset' => '-3 weeks'],
            ['title' => 'Documentation OpenAPI (NelmioApiDoc)', 'status' => 'todo', 'created_offset' => '-2 weeks 5 days'],
            ['title' => 'Déploiement sur Heroku/Railway', 'status' => 'todo', 'created_offset' => '-2 weeks'],
        ];
        $this->createTasksForProject('Todo List API (oui encore une)', $tasksBob2, $manager);

        // Projet 3 : Système Auth JWT
        $tasksBob3 = [
            ['title' => 'Comprendre structure JWT (header.payload.signature)', 'status' => 'done', 'created_offset' => '-3 weeks'],
            ['title' => 'Génération access token (expire 15min)', 'status' => 'done', 'created_offset' => '-2 weeks 5 days'],
            ['title' => 'Refresh token (expire 7 jours)', 'status' => 'in_progress', 'created_offset' => '-2 weeks'],
            ['title' => 'Middleware validation token', 'status' => 'in_progress', 'created_offset' => '-1 week 5 days'],
            ['title' => 'Système blacklist (logout/révocation)', 'status' => 'todo', 'created_offset' => '-1 week'],
        ];
        $this->createTasksForProject('Système Auth JWT from scratch', $tasksBob3, $manager);

        // ========================================
        // 4- TÂCHES CLARA - Reconversion
        // ========================================

        // Projet 0 : Calculatrice Web
        $tasksClara0 = [
            ['title' => 'Comprendre structure HTML (div, button, input)', 'status' => 'done', 'created_offset' => '-3 weeks'],
            ['title' => 'Styliser avec CSS (grid layout pour boutons)', 'status' => 'done', 'created_offset' => '-2 weeks 5 days'],
            ['title' => 'JavaScript : addEventListener sur boutons', 'status' => 'done', 'created_offset' => '-2 weeks 3 days'],
            ['title' => 'Pourquoi mon console.log ne s\'affiche pas ?', 'status' => 'done', 'created_offset' => '-2 weeks 2 days'],
            ['title' => 'Gestion erreurs (NaN, Infinity)', 'status' => 'in_progress', 'created_offset' => '-2 weeks'],
        ];
        $this->createTasksForProject('Calculatrice Web (HTML/CSS/JS)', $tasksClara0, $manager);

        // Projet 1 : Site Vitrine Cours Maths
        $tasksClara1 = [
            ['title' => 'Créer structure HTML (header, nav, main, footer)', 'status' => 'done', 'created_offset' => '-2 weeks 3 days'],
            ['title' => 'CSS : faire un vrai design (pas Comic Sans !)', 'status' => 'in_progress', 'created_offset' => '-2 weeks'],
            ['title' => 'Formulaire de contact avec validation', 'status' => 'in_progress', 'created_offset' => '-1 week 5 days'],
            ['title' => 'Responsive : mon site est cassé sur mobile...', 'status' => 'todo', 'created_offset' => '-1 week 3 days'],
            ['title' => 'Déployer sur GitHub Pages', 'status' => 'todo', 'created_offset' => '-1 week'],
        ];
        $this->createTasksForProject('Site Vitrine - Cours de Maths', $tasksClara1, $manager);

        // Projet 2 : Convertisseur Unités
        $tasksClara2 = [
            ['title' => 'Interface : sélecteurs et champs input', 'status' => 'done', 'created_offset' => '-1 week 5 days'],
            ['title' => 'Formules de conversion (m → cm, km, etc.)', 'status' => 'in_progress', 'created_offset' => '-1 week 3 days'],
            ['title' => 'Affichage résultat en temps réel', 'status' => 'in_progress', 'created_offset' => '-1 week'],
            ['title' => 'Ajouter conversions surfaces et volumes', 'status' => 'todo', 'created_offset' => '-5 days'],
            ['title' => 'Améliorer UX (icons, animations)', 'status' => 'todo', 'created_offset' => '-3 days'],
        ];
        $this->createTasksForProject('Convertisseur Unités Mathématiques', $tasksClara2, $manager);

        $manager->flush();
    }

    /**
     * Helper pour créer des tâches avec dates échelonnées
     * 
     * @param string $projectName Nom exact du projet dans la BDD
     * @param array $tasksData Tableau de tâches avec title, status, created_offset
     * @param ObjectManager $manager Doctrine ObjectManager
     */
    private function createTasksForProject(string $projectName, array $tasksData, ObjectManager $manager): void
    {
        // Récupérer le projet depuis la BDD
        $projectRepository = $this->em->getRepository(Project::class);
        $project = $projectRepository->findOneBy(['name' => $projectName]);

        if (!$project) {
            throw new \Exception("Project '$projectName' not found! Load ProjectFixtures first.");
        }

        foreach ($tasksData as $position => $taskData) {
            $task = new Task();
            $task->setTitle($taskData['title']);
            $task->setDescription(null); // Description vide (nullable)
            $task->setStatus($taskData['status']);
            $task->setPosition($position + 1);
            $task->setProject($project);
            $task->setCreatedAt(new \DateTime($taskData['created_offset']));

            $manager->persist($task);
        }
    }

    public static function getGroups(): array
    {
        return ['task'];
    }
}