<?php

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
        // 1️⃣ Récupérer le User créé par UserFixtures
        $user = $this->getReference('user_anthony', User::class);

        // 2️⃣ Créer 3 projets
        $projectsData = [
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

        foreach ($projectsData as $index => $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setDescription($data['description']);
            $project->setOwner($user);  // ← Relation ManyToOne
            $project->setCreatedAt(new \DateTime());

            $manager->persist($project);
            
            // 3️⃣ Créer une référence pour TaskFixtures
            $this->addReference('project_' . $index, $project);
        }

        $manager->flush();
    }

    // 4️⃣ Dépendance : exécuter APRÈS UserFixtures
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}