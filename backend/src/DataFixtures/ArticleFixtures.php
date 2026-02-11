<?php
// src/DataFixtures/ArticleFixtures.php

namespace App\DataFixtures;

use App\Document\Article;
use App\Entity\User;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;

class ArticleFixtures extends Fixture
{
    public function __construct(
        private DocumentManager $dm,
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

        // Convertir en string pour MongoDB
        $anthonyId = (string) $anthony->getId();
        $aliceId = (string) $alice->getId();
        $bobId = (string) $bob->getId();
        $claraId = (string) $clara->getId();

        // ========================================
        // ARTICLES GRAFIKART (5 articles - le plus représenté)
        // ========================================

        $article1 = new Article();
        $article1->setTitle('Débuter avec Symfony 7 : Guide complet pour débutants');
        $article1->setUrl('https://grafikart.fr/tutoriels/symfony-7-debutant-2024');
        $article1->setDescription('Apprenez les bases de Symfony 7 : installation, premier controller, routing, Twig. Tutoriel complet pour démarrer sereinement avec le framework PHP le plus populaire.');
        $article1->setSource('Grafikart');
        $article1->setTags(['php', 'symfony', 'tutorial', 'débutant']);
        $article1->setPublishedAt(new \DateTimeImmutable('-55 days'));
        $article1->setUserId(null); // Article RSS public
        // Lu par : Anthony, Bob, Clara (3 personnes)
        $article1->markAsReadByUser($anthonyId);
        $article1->markAsReadByUser($bobId);
        $article1->markAsReadByUser($claraId);
        // Favori : Clara (utile pour débutante)
        $article1->addToFavorites($claraId);
        $this->dm->persist($article1);

        $article2 = new Article();
        $article2->setTitle('Docker pour les développeurs PHP : De zéro à la production');
        $article2->setUrl('https://grafikart.fr/tutoriels/docker-php-production-2024');
        $article2->setDescription('Maîtrisez Docker pour vos projets PHP : Dockerfile, docker-compose, volumes, networks, optimisation des images, déploiement en production. Guide pratique avec exemples Symfony.');
        $article2->setSource('Grafikart');
        $article2->setTags(['docker', 'php', 'devops', 'deployment']);
        $article2->setPublishedAt(new \DateTimeImmutable('-48 days'));
        $article2->setUserId(null);
        // Lu par : Anthony, Bob, Alice (3 personnes)
        $article2->markAsReadByUser($anthonyId);
        $article2->markAsReadByUser($bobId);
        $article2->markAsReadByUser($aliceId);
        // Favori : Anthony, Bob (pertinent pour devops)
        $article2->addToFavorites($anthonyId);
        $article2->addToFavorites($bobId);
        $this->dm->persist($article2);

        $article3 = new Article();
        $article3->setTitle('Tests unitaires avec PHPUnit : Bonnes pratiques 2024');
        $article3->setUrl('https://grafikart.fr/tutoriels/phpunit-tests-unitaires-2024');
        $article3->setDescription('Apprenez à écrire des tests unitaires efficaces avec PHPUnit : mocks, stubs, fixtures, code coverage. Améliorez la qualité de votre code PHP avec une suite de tests robuste.');
        $article3->setSource('Grafikart');
        $article3->setTags(['php', 'testing', 'phpunit', 'quality']);
        $article3->setPublishedAt(new \DateTimeImmutable('-42 days'));
        $article3->setUserId(null);
        // Lu par : Anthony, Bob (2 personnes - testing avancé)
        $article3->markAsReadByUser($anthonyId);
        $article3->markAsReadByUser($bobId);
        // Favori : Bob (veut améliorer ses tests)
        $article3->addToFavorites($bobId);
        $this->dm->persist($article3);

        $article4 = new Article();
        $article4->setTitle('JavaScript moderne : Les nouveautés ES2024 à connaître');
        $article4->setUrl('https://grafikart.fr/tutoriels/javascript-es2024-nouveautes');
        $article4->setDescription('Découvrez les nouvelles fonctionnalités JavaScript ES2024 : Array grouping, Temporal API, decorators, pattern matching. Exemples pratiques et cas d\'usage concrets.');
        $article4->setSource('Grafikart');
        $article4->setTags(['javascript', 'es2024', 'frontend', 'modern-js']);
        $article4->setPublishedAt(new \DateTimeImmutable('-35 days'));
        $article4->setUserId(null);
        // Lu par : Anthony, Alice, Clara (3 personnes - JS pour tous)
        $article4->markAsReadByUser($anthonyId);
        $article4->markAsReadByUser($aliceId);
        $article4->markAsReadByUser($claraId);
        // Favori : Alice (frontend focus)
        $article4->addToFavorites($aliceId);
        $this->dm->persist($article4);

        $article5 = new Article();
        $article5->setTitle('Optimiser les performances web : Guide ultime 2024');
        $article5->setUrl('https://grafikart.fr/tutoriels/performances-web-optimisation-2024');
        $article5->setDescription('Techniques avancées d\'optimisation web : lazy loading, code splitting, compression, CDN, critical CSS, preload/prefetch. Atteignez un score Lighthouse > 95 sur tous vos projets.');
        $article5->setSource('Grafikart');
        $article5->setTags(['performance', 'optimization', 'web', 'lighthouse']);
        $article5->setPublishedAt(new \DateTimeImmutable('-28 days'));
        $article5->setUserId(null);
        // Lu par : Anthony, Alice (2 personnes - performance experts)
        $article5->markAsReadByUser($anthonyId);
        $article5->markAsReadByUser($aliceId);
        // Favori : Anthony (pertinent pour MY-ANKODE)
        $article5->addToFavorites($anthonyId);
        $this->dm->persist($article5);

        // ========================================
        // ARTICLES DEV.TO (3 articles)
        // ========================================

        $article6 = new Article();
        $article6->setTitle('Building RESTful APIs with Symfony 7 and API Platform');
        $article6->setUrl('https://dev.to/symfony/building-restful-apis-symfony-7-2024');
        $article6->setDescription('Complete guide to building production-ready REST APIs with Symfony 7 and API Platform: serialization, validation, pagination, filtering, authentication with JWT.');
        $article6->setSource('Dev.to');
        $article6->setTags(['symfony', 'api', 'rest', 'api-platform']);
        $article6->setPublishedAt(new \DateTimeImmutable('-50 days'));
        $article6->setUserId(null);
        // Lu par : Anthony, Bob (2 personnes - backend API)
        $article6->markAsReadByUser($anthonyId);
        $article6->markAsReadByUser($bobId);
        // Favori : Bob (API REST specialist)
        $article6->addToFavorites($bobId);
        $this->dm->persist($article6);

        $article7 = new Article();
        $article7->setTitle('CSS Grid Layout: Advanced Patterns and Real-World Examples');
        $article7->setUrl('https://dev.to/css/grid-layout-advanced-patterns-2024');
        $article7->setDescription('Master CSS Grid with advanced layout patterns: masonry grids, responsive dashboards, magazine layouts. Real-world examples with CodePen demos.');
        $article7->setSource('Dev.to');
        $article7->setTags(['css', 'grid', 'layout', 'responsive']);
        $article7->setPublishedAt(new \DateTimeImmutable('-40 days'));
        $article7->setUserId(null);
        // Lu par : Alice, Clara (2 personnes - CSS layout)
        $article7->markAsReadByUser($aliceId);
        $article7->markAsReadByUser($claraId);
        // Favori : Alice (CSS Grid enthusiast)
        $article7->addToFavorites($aliceId);
        $this->dm->persist($article7);

        $article8 = new Article();
        $article8->setTitle('MongoDB Best Practices for PHP Developers in 2024');
        $article8->setUrl('https://dev.to/mongodb/best-practices-php-developers-2024');
        $article8->setDescription('Learn MongoDB optimization techniques for PHP: indexing strategies, aggregation pipelines, schema design patterns, Doctrine ODM tips, performance monitoring.');
        $article8->setSource('Dev.to');
        $article8->setTags(['mongodb', 'php', 'nosql', 'doctrine-odm']);
        $article8->setPublishedAt(new \DateTimeImmutable('-32 days'));
        $article8->setUserId(null);
        // Lu par : Anthony, Bob (2 personnes - backend DB)
        $article8->markAsReadByUser($anthonyId);
        $article8->markAsReadByUser($bobId);
        // Pas de favori (intéressant mais pas critique)
        $this->dm->persist($article8);

        // ========================================
        // ARTICLES KORBEN.INFO (3 articles)
        // ========================================

        $article9 = new Article();
        $article9->setTitle('Les meilleurs outils open-source pour développeurs en 2024');
        $article9->setUrl('https://korben.info/meilleurs-outils-open-source-dev-2024.html');
        $article9->setDescription('Sélection des outils open-source indispensables pour développeurs : IDE, extensions VS Code, outils DevOps, monitoring, testing. Gagnez en productivité avec ces pépites gratuites.');
        $article9->setSource('Korben.info');
        $article9->setTags(['open-source', 'tools', 'productivity', 'devops']);
        $article9->setPublishedAt(new \DateTimeImmutable('-45 days'));
        $article9->setUserId(null);
        // Lu par : Anthony, Alice, Bob, Clara (4 personnes - outils universels)
        $article9->markAsReadByUser($anthonyId);
        $article9->markAsReadByUser($aliceId);
        $article9->markAsReadByUser($bobId);
        $article9->markAsReadByUser($claraId);
        // Favori : Anthony, Clara (outils pratiques)
        $article9->addToFavorites($anthonyId);
        $article9->addToFavorites($claraId);
        $this->dm->persist($article9);

        $article10 = new Article();
        $article10->setTitle('Cybersécurité : Protéger son application web en 2024');
        $article10->setUrl('https://korben.info/cybersecurite-proteger-application-web-2024.html');
        $article10->setDescription('Guide complet de sécurisation d\'applications web : OWASP Top 10, CSRF, XSS, injection SQL, authentification sécurisée, HTTPS, headers de sécurité. Checklist complète incluse.');
        $article10->setSource('Korben.info');
        $article10->setTags(['security', 'cybersecurity', 'owasp', 'web']);
        $article10->setPublishedAt(new \DateTimeImmutable('-38 days'));
        $article10->setUserId(null);
        // Lu par : Anthony, Bob (2 personnes - sécurité backend)
        $article10->markAsReadByUser($anthonyId);
        $article10->markAsReadByUser($bobId);
        // Favori : Anthony (sécurité MY-ANKODE)
        $article10->addToFavorites($anthonyId);
        $this->dm->persist($article10);

        $article11 = new Article();
        $article11->setTitle('GitHub Copilot vs autres IA de code : Comparatif 2024');
        $article11->setUrl('https://korben.info/github-copilot-vs-ia-code-comparatif-2024.html');
        $article11->setDescription('Comparatif détaillé des assistants IA pour développeurs : GitHub Copilot, Cursor, Tabnine, Amazon CodeWhisperer. Performances, prix, langages supportés, intégration IDE.');
        $article11->setSource('Korben.info');
        $article11->setTags(['ai', 'copilot', 'productivity', 'coding-assistant']);
        $article11->setPublishedAt(new \DateTimeImmutable('-25 days'));
        $article11->setUserId(null);
        // Lu par : Alice, Bob, Clara (3 personnes - curiosité IA)
        $article11->markAsReadByUser($aliceId);
        $article11->markAsReadByUser($bobId);
        $article11->markAsReadByUser($claraId);
        // Favori : Clara (aide débutante)
        $article11->addToFavorites($claraId);
        $this->dm->persist($article11);

        // ========================================
        // ARTICLES CSS-TRICKS (2 articles)
        // ========================================

        $article12 = new Article();
        $article12->setTitle('Modern CSS Techniques You Should Be Using in 2024');
        $article12->setUrl('https://css-tricks.com/modern-css-techniques-2024');
        $article12->setDescription('Discover cutting-edge CSS features: container queries, :has() selector, cascade layers, CSS nesting, color-mix(), subgrid. Browser support and practical examples included.');
        $article12->setSource('CSS-Tricks');
        $article12->setTags(['css', 'modern-css', 'frontend', 'new-features']);
        $article12->setPublishedAt(new \DateTimeImmutable('-30 days'));
        $article12->setUserId(null);
        // Lu par : Anthony, Alice (2 personnes - CSS moderne)
        $article12->markAsReadByUser($anthonyId);
        $article12->markAsReadByUser($aliceId);
        // Favori : Alice (CSS power user)
        $article12->addToFavorites($aliceId);
        $this->dm->persist($article12);

        $article13 = new Article();
        $article13->setTitle('Accessible Web Components: A Complete Guide');
        $article13->setUrl('https://css-tricks.com/accessible-web-components-guide-2024');
        $article13->setDescription('Build accessible custom elements with ARIA attributes, keyboard navigation, screen reader support. Comprehensive guide with code examples and WCAG compliance checklist.');
        $article13->setSource('CSS-Tricks');
        $article13->setTags(['accessibility', 'web-components', 'aria', 'wcag']);
        $article13->setPublishedAt(new \DateTimeImmutable('-22 days'));
        $article13->setUserId(null);
        // Lu par : Anthony, Alice (2 personnes - accessibilité)
        $article13->markAsReadByUser($anthonyId);
        $article13->markAsReadByUser($aliceId);
        // Pas de favori (lu mais pas critique pour projets actuels)
        $this->dm->persist($article13);

        // ========================================
        // ARTICLES SMASHING MAGAZINE (2 articles)
        // ========================================

        $article14 = new Article();
        $article14->setTitle('Designing Better Dark Modes: UX Best Practices');
        $article14->setUrl('https://smashingmagazine.com/designing-better-dark-modes-2024');
        $article14->setDescription('Create exceptional dark mode experiences: color palette selection, contrast ratios, user preference detection, smooth transitions, accessibility considerations. Case studies from top apps.');
        $article14->setSource('Smashing Magazine');
        $article14->setTags(['dark-mode', 'ux', 'design', 'accessibility']);
        $article14->setPublishedAt(new \DateTimeImmutable('-18 days'));
        $article14->setUserId(null);
        // Lu par : Anthony, Alice (2 personnes - dark mode MY-ANKODE)
        $article14->markAsReadByUser($anthonyId);
        $article14->markAsReadByUser($aliceId);
        // Favori : Anthony (feature MY-ANKODE)
        $article14->addToFavorites($anthonyId);
        $this->dm->persist($article14);

        $article15 = new Article();
        $article15->setTitle('Core Web Vitals Optimization: The Definitive Guide');
        $article15->setUrl('https://smashingmagazine.com/core-web-vitals-optimization-2024');
        $article15->setDescription('Master Google Core Web Vitals: LCP, FID, CLS optimization strategies. Image optimization, font loading, JavaScript execution, layout shifts prevention. Real-world performance improvements.');
        $article15->setSource('Smashing Magazine');
        $article15->setTags(['performance', 'core-web-vitals', 'seo', 'optimization']);
        $article15->setPublishedAt(new \DateTimeImmutable('-12 days'));
        $article15->setUserId(null);
        // Lu par : Alice (1 personne - performance frontend)
        $article15->markAsReadByUser($aliceId);
        // Favori : Alice (SEO + perf)
        $article15->addToFavorites($aliceId);
        $this->dm->persist($article15);

        // Flush MongoDB
        $this->dm->flush();

        echo "\n 15 articles RSS chargés dans MongoDB\n";
        echo " Répartition sources : Grafikart (5), Dev.to (3), Korben.info (3), CSS-Tricks (2), Smashing Magazine (2)\n";
        echo " Lectures : Anthony (9), Alice (7), Bob (6), Clara (4)\n";
        echo " Favoris : Anthony (4), Alice (4), Bob (3), Clara (3)\n";
    }
}