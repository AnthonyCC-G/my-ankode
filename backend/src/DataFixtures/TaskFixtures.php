<?php
// src/DataFixtures/TaskFixtures.php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\Project;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $statusList = ['todo', 'in_progress', 'done'];
        
        // Tâches pour les projets d'Alice 
        $tasksDataAlice = [
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

        foreach ($tasksDataAlice as $projectIndex => $tasks) {
            $project = $this->getReference('project_alice_' . $projectIndex, Project::class);

            foreach ($tasks as $position => $taskData) {
                $task = new Task();
                $task->setTitle($taskData['title']);
                $task->setDescription('Description de : ' . $taskData['title']);
                $task->setStatus($taskData['status']);
                $task->setPosition($position + 1);
                $task->setProject($project);
                $task->setCreatedAt(new \DateTime());

                $manager->persist($task);
            }
        }

        // Tâches pour les projets de Marie 
        $tasksDataMarie = [
            // Projet 0 : Blog Cuisine
            0 => [
                ['title' => 'Installer WordPress', 'status' => 'done'],
                ['title' => 'Créer thème personnalisé', 'status' => 'in_progress'],
                ['title' => 'Ajouter 10 recettes', 'status' => 'todo'],
            ],
            // Projet 1 : Dashboard Analytics
            1 => [
                ['title' => 'Configurer API Google Analytics', 'status' => 'in_progress'],
                ['title' => 'Créer graphiques temps réel', 'status' => 'todo'],
            ],
        ];

        foreach ($tasksDataMarie as $projectIndex => $tasks) {
            $project = $this->getReference('project_marie_' . $projectIndex, Project::class);

            foreach ($tasks as $position => $taskData) {
                $task = new Task();
                $task->setTitle($taskData['title']);
                $task->setDescription('Description de : ' . $taskData['title']);
                $task->setStatus($taskData['status']);
                $task->setPosition($position + 1);
                $task->setProject($project);
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