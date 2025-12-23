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
        // Récupérer les 2 utilisateurs
        $userAnthony = $this->getReference('user_anthony', User::class);
        $userMarie = $this->getReference('user_marie', User::class);

        // 📁 Projets pour Anthony (3 projets)
        $projectsAnthony = [
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

        foreach ($projectsAnthony as $index => $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setOwner($userAnthony);
            $project->setCreatedAt(new \DateTime());

            $manager->persist($project);
            
            // Référence pour TaskFixtures
            $this->addReference('project_anthony_' . $index, $project);
        }

        // 📁 Projets pour Marie (2 projets)
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
            
            // Référence pour TaskFixtures si besoin
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