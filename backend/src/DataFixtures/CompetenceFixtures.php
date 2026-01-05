<?php

namespace App\DataFixtures;

use App\Entity\Competence;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CompetenceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les 2 utilisateurs de test
        $anthony = $this->getReference('user_anthony', User::class);
        $marie = $this->getReference('user_marie', User::class);

        // === COMPÉTENCES ANTHONY (5 compétences) ===
        $competencesAnthony = [
            [
                'name' => 'PHP',
                'level' => 4,
                'notes' => 'Maîtrise de Symfony, Doctrine et les design patterns',
                'projects_links' => '1,2',
                'snippets_links' => null
            ],
            [
                'name' => 'JavaScript',
                'level' => 3,
                'notes' => 'ES6+, Vanilla JS, manipulation du DOM',
                'projects_links' => '1',
                'snippets_links' => null
            ],
            [
                'name' => 'PostgreSQL',
                'level' => 3,
                'notes' => 'Requêtes SQL, jointures, optimisation',
                'projects_links' => '1,2',
                'snippets_links' => null
            ],
            [
                'name' => 'Docker',
                'level' => 2,
                'notes' => 'Docker Compose, conteneurisation, déploiement',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Git',
                'level' => 4,
                'notes' => 'Git Flow, branches, résolution de conflits',
                'projects_links' => null,
                'snippets_links' => null
            ]
        ];

        foreach ($competencesAnthony as $data) {
            $competence = new Competence();
            $competence->setOwner($anthony);
            $competence->setName($data['name']);
            $competence->setLevel($data['level']);
            $competence->setNotes($data['notes']);
            $competence->setProjectsLinks($data['projects_links']);
            $competence->setSnippetsLinks($data['snippets_links']);

            $manager->persist($competence);
        }

        // === COMPÉTENCES MARIE (3 compétences) ===
        $competencesMarie = [
            [
                'name' => 'HTML/CSS',
                'level' => 5,
                'notes' => 'Flexbox, Grid, animations CSS, responsive design',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'React',
                'level' => 2,
                'notes' => 'Débutante en React, hooks basiques',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'MongoDB',
                'level' => 1,
                'notes' => 'Bases de données NoSQL, premiers pas',
                'projects_links' => null,
                'snippets_links' => null
            ]
        ];

        foreach ($competencesMarie as $data) {
            $competence = new Competence();
            $competence->setOwner($marie);
            $competence->setName($data['name']);
            $competence->setLevel($data['level']);
            $competence->setNotes($data['notes']);
            $competence->setProjectsLinks($data['projects_links']);
            $competence->setSnippetsLinks($data['snippets_links']);

            $manager->persist($competence);
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