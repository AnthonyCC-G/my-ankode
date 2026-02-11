<?php
// src/DataFixtures/CompetenceFixtures.php

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
        // Récupérer les 4 utilisateurs
        $anthony = $this->getReference('user_anthony', User::class);
        $alice = $this->getReference('user_alice', User::class);
        $bob = $this->getReference('user_bob', User::class);
        $clara = $this->getReference('user_clara', User::class);

        // ========================================
        // 1- COMPÉTENCES ANTHONY - Les 8 UC du référentiel DWWM
        // ========================================
        $competencesAnthony = [
            // BLOC 1 : Front-end
            [
                'name' => 'UC1 - Environnement de travail',
                'level' => 4,
                'notes' => 'Installer et configurer son environnement de travail en fonction du projet web ou web mobile',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'UC2 - Maquettage',
                'level' => 3,
                'notes' => 'Maquetter des interfaces utilisateur web ou web mobile (wireframes, mockups, prototypes)',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'UC3 - Interfaces statiques',
                'level' => 4,
                'notes' => 'Réaliser des interfaces utilisateur statiques web ou web mobile (HTML, CSS, responsive)',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'UC4 - Interfaces dynamiques',
                'level' => 4,
                'notes' => 'Développer la partie dynamique des interfaces utilisateur web ou web mobile (JavaScript, frameworks)',
                'projects_links' => null,
                'snippets_links' => null
            ],
            // BLOC 2 : Back-end
            [
                'name' => 'UC5 - Base de données relationnelle',
                'level' => 4,
                'notes' => 'Mettre en place une base de données relationnelle (conception, normalisation, SQL)',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'UC6 - Accès aux données',
                'level' => 4,
                'notes' => 'Développer des composants d\'accès aux données SQL et NoSQL (ORM, requêtes optimisées)',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'UC7 - Composants métier serveur',
                'level' => 3,
                'notes' => 'Développer des composants métier côté serveur (API REST, architecture, sécurité)',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'UC8 - Documentation et déploiement',
                'level' => 3,
                'notes' => 'Documenter le déploiement d\'une application dynamique web ou web mobile (Docker, CI/CD, docs techniques)',
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

        // ========================================
        // 2- COMPÉTENCES ALICE - Frontend
        // ========================================
        $competencesAlice = [
            [
                'name' => 'HTML/CSS',
                'level' => 5,
                'notes' => 'Sémantique HTML5, Flexbox, Grid, animations CSS. Enfin compris le centrage vertical !',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'JavaScript ES6+',
                'level' => 4,
                'notes' => 'Manipulation DOM, async/await, promises, modules. Arrow functions = vie',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'React',
                'level' => 3,
                'notes' => 'Hooks (useState, useEffect), composants fonctionnels. Encore confuse sur useContext',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Responsive Design',
                'level' => 5,
                'notes' => 'Mobile-first, breakpoints, media queries. Mon site fonctionne sur TOUS les devices maintenant !',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Figma',
                'level' => 4,
                'notes' => 'Wireframes, mockups, prototypes interactifs. Auto Layout = game changer',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Accessibilité (WCAG)',
                'level' => 3,
                'notes' => 'ARIA, contraste couleurs, navigation clavier. Important mais complexe',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Git & GitHub',
                'level' => 4,
                'notes' => 'Branches, merge, pull requests. Moins peur des conflits maintenant',
                'projects_links' => null,
                'snippets_links' => null
            ]
        ];

        foreach ($competencesAlice as $data) {
            $competence = new Competence();
            $competence->setOwner($alice);
            $competence->setName($data['name']);
            $competence->setLevel($data['level']);
            $competence->setNotes($data['notes']);
            $competence->setProjectsLinks($data['projects_links']);
            $competence->setSnippetsLinks($data['snippets_links']);

            $manager->persist($competence);
        }

        // ========================================
        // 3- COMPÉTENCES BOB - Backend
        // ========================================
        $competencesBob = [
            [
                'name' => 'PHP 8.3',
                'level' => 4,
                'notes' => 'POO, namespaces, traits, attributs. Typed properties = moins de bugs',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Symfony 7',
                'level' => 4,
                'notes' => 'Controllers, services, dependency injection. Doctrine ORM > SQL pur',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'PostgreSQL',
                'level' => 4,
                'notes' => 'Requêtes complexes, jointures, index, transactions. EXPLAIN ANALYZE = meilleur ami',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'MongoDB',
                'level' => 2,
                'notes' => 'Bases NoSQL, documents, agrégations. Encore habitué au relationnel',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'API REST',
                'level' => 4,
                'notes' => 'Design API, versioning, documentation OpenAPI. JWT = token magique',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Docker',
                'level' => 3,
                'notes' => 'Conteneurisation, Docker Compose, multi-stage builds. Plus de "ça marche sur ma machine"',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Tests PHPUnit',
                'level' => 3,
                'notes' => 'Tests unitaires, mocks, coverage. Écrire les tests AVANT le code = discipline',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Git Flow',
                'level' => 4,
                'notes' => 'Feature branches, main/develop, releases. Organisation = clé du succès',
                'projects_links' => null,
                'snippets_links' => null
            ]
        ];

        foreach ($competencesBob as $data) {
            $competence = new Competence();
            $competence->setOwner($bob);
            $competence->setName($data['name']);
            $competence->setLevel($data['level']);
            $competence->setNotes($data['notes']);
            $competence->setProjectsLinks($data['projects_links']);
            $competence->setSnippetsLinks($data['snippets_links']);

            $manager->persist($competence);
        }

        // ========================================
        // 4- COMPÉTENCES CLARA - Reconversion (progression visible)
        // ========================================
        $competencesClara = [
            [
                'name' => 'HTML',
                'level' => 3,
                'notes' => 'Structure de page, balises sémantiques, formulaires. Plus simple que prévu !',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'CSS',
                'level' => 2,
                'notes' => 'Sélecteurs, box model, Flexbox de base. Le positionnement reste un mystère...',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'JavaScript',
                'level' => 2,
                'notes' => 'Variables, fonctions, événements. console.log() = outil de debug préféré',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Git',
                'level' => 2,
                'notes' => 'add, commit, push. Git = sauvegarde magique de mon code !',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'VS Code',
                'level' => 3,
                'notes' => 'Extensions, raccourcis, debugging. Pourquoi je codais sur Notepad avant ?!',
                'projects_links' => null,
                'snippets_links' => null
            ],
            [
                'name' => 'Logique de programmation',
                'level' => 3,
                'notes' => 'Conditions, boucles, algorithmes. Mes années de maths servent enfin à quelque chose !',
                'projects_links' => null,
                'snippets_links' => null
            ]
        ];

        foreach ($competencesClara as $data) {
            $competence = new Competence();
            $competence->setOwner($clara);
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