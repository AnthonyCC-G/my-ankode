<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\Project;  // ← IMPORTANT : Importer l'entité Project
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 1️⃣ Statuts variés pour rendre le Kanban réaliste
        $statusList = ['todo', 'in_progress', 'done'];
        
        // 2️⃣ Exemples de tâches par projet
        $tasksData = [
            // Projet 0 : Site E-commerce
            0 => [
                ['title' => 'Créer page d\'accueil', 'status' => 'todo'],
                ['title' => 'Designer le logo', 'status' => 'in_progress'],
                ['title' => 'Intégrer le panier', 'status' => 'in_progress'],
                ['title' => 'Tests unitaires', 'status' => 'done'],
                ['title' => 'Déploiement', 'status' => 'todo'],
            ],
            // Projet 1 : Application Mobile
            1 => [
                ['title' => 'Maquettes Figma', 'status' => 'done'],
                ['title' => 'Authentification', 'status' => 'in_progress'],
                ['title' => 'Dashboard utilisateur', 'status' => 'todo'],
                ['title' => 'Notifications push', 'status' => 'todo'],
                ['title' => 'Tests iOS/Android', 'status' => 'todo'],
            ],
            // Projet 2 : Portfolio Personnel
            2 => [
                ['title' => 'Choisir template', 'status' => 'done'],
                ['title' => 'Rédiger biographie', 'status' => 'in_progress'],
                ['title' => 'Ajouter projets réalisés', 'status' => 'todo'],
                ['title' => 'Section contact', 'status' => 'todo'],
                ['title' => 'Optimisation SEO', 'status' => 'todo'],
            ],
        ];

        // 3️⃣ Créer les tâches pour chaque projet
        foreach ($tasksData as $projectIndex => $tasks) {
            // Récupérer le projet via sa référence
            $project = $this->getReference('project_' . $projectIndex, Project::class);

            foreach ($tasks as $position => $taskData) {
                $task = new Task();
                $task->setTitle($taskData['title']);
                $task->setDescription('Description de : ' . $taskData['title']);
                $task->setStatus($taskData['status']);
                $task->setPosition($position + 1);  // Position 1, 2, 3, 4, 5
                $task->setProject($project);  // ← Relation ManyToOne
                $task->setCreatedAt(new \DateTime());

                $manager->persist($task);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
        ];
    }
}