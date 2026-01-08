<?php
// src/DataFixtures/ProjectFixtures.php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les 2 utilisateurs LAMBDA (pas l'admin)
        $userAlice = $this->getReference('user_alice', User::class);
        $userMarie = $this->getReference('user_marie', User::class);

        //  Projets pour Alice (3 projets)
        $projectsAlice = [
            [
                'name' => 'Site E-commerce',
                'description' => 'Boutique en ligne avec panier et paiement sécurisé'
            ],
            [
                'name' => 'Application Mobile',
                'description' => 'App iOS/Android pour gestion de tâches quotidiennes'
            ],
            [
                'name' => 'Portfolio Personnel',
                'description' => 'Site vitrine pour présenter mes compétences et projets'
            ]
        ];

        foreach ($projectsAlice as $index => $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setOwner($userAlice);
            $project->setCreatedAt(new \DateTime());

            $manager->persist($project);
            
            // Référence pour TaskFixtures
            $this->addReference('project_alice_' . $index, $project);
        }

        // Projets pour Marie (2 projets)
        $projectsMarie = [
            [
                'name' => 'Blog Cuisine',
                'description' => 'Blog de recettes avec système de commentaires'
            ],
            [
                'name' => 'Dashboard Analytics',
                'description' => 'Tableau de bord de statistiques temps réel'
            ]
        ];

        foreach ($projectsMarie as $index => $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setOwner($userMarie);
            $project->setCreatedAt(new \DateTime());

            $manager->persist($project);
            
            // Référence pour TaskFixtures
            $this->addReference('project_marie_' . $index, $project);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}