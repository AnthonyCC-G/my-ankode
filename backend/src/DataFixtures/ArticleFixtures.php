<?php

namespace App\DataFixtures;

use App\Document\Article;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour charger des articles de demo dans MongoDB
 * Ces articles permettent de tester l'interface Veille sans connexion internet
 * 
 * Les articles sont PUBLICS (userId = null)
 * Tous les utilisateurs voient les mêmes articles, mais chacun peut avoir ses propres favoris/lectures
 * 
 * Usage : php bin/console doctrine:mongodb:fixtures:load --append
 */
class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $demoArticles = $this->getDemoArticles();

        $count = 0;
        
        // Tous les articles sont publics (userId = null)
        foreach ($demoArticles as $data) {
            $article = new Article();
            $article->setTitle($data['title']);
            $article->setUrl($data['url']);
            $article->setDescription($data['description']);
            $article->setSource($data['source']);
            $article->setPublishedAt($data['publishedAt']);
            
            // Articles publics RSS
            $article->setUserId(null);
            
            // Les arrays readBy et favoritedBy sont initialisés vides par défaut

            $manager->persist($article);
            $count++;
        }

        $manager->flush();

        echo " {$count} articles publics de demo chargés dans MongoDB\n";
        echo " Ces articles sont visibles par TOUS les utilisateurs\n";
        echo " Chaque utilisateur peut avoir ses propres favoris et états de lecture\n";
    }

    /**
     * Retourne un tableau d'articles de demo
     * Total : 30 articles pour tester la pagination (20 par page)
     */
    private function getDemoArticles(): array
    {
        return [
            // Articles recents (page 1)
            [
                'title' => 'Symfony 7.2 : Les nouveautes a ne pas manquer',
                'url' => 'https://symfony.com/blog/symfony-7-2',
                'description' => 'Decouvrez les nouvelles fonctionnalites de Symfony 7.2 : performance amelioree, nouveau composant Scheduler, et bien plus encore.',
                'source' => 'Symfony Blog',
                'publishedAt' => new \DateTimeImmutable('-1 hour')
            ],
            [
                'title' => 'Docker Compose v2.25 : Gestion multi-environnements simplifiee',
                'url' => 'https://docs.docker.com/compose/releases/',
                'description' => 'La nouvelle version de Docker Compose facilite la gestion de plusieurs environnements avec des profiles avances.',
                'source' => 'Docker Blog',
                'publishedAt' => new \DateTimeImmutable('-3 hours')
            ],
            [
                'title' => 'MongoDB 8.0 : Performances records sur les agregations',
                'url' => 'https://www.mongodb.com/blog/post/mongodb-8-0',
                'description' => 'MongoDB 8.0 apporte des optimisations majeures sur les pipelines d\'agregation, reduisant les temps de requete de 40%.',
                'source' => 'MongoDB Blog',
                'publishedAt' => new \DateTimeImmutable('-5 hours')
            ],
            [
                'title' => 'PostgreSQL 17 : Les nouvelles fonctionnalites SQL/JSON',
                'url' => 'https://www.postgresql.org/about/news/postgresql-17',
                'description' => 'PostgreSQL 17 introduit de puissantes fonctions SQL/JSON pour manipuler des donnees hybrides relationnelles et NoSQL.',
                'source' => 'PostgreSQL News',
                'publishedAt' => new \DateTimeImmutable('-8 hours')
            ],
            [
                'title' => 'Angular 18 : Signals et Reactivity par defaut',
                'url' => 'https://angular.dev/roadmap',
                'description' => 'Angular 18 marque un tournant avec les Signals integres nativement, offrant une reactivite simplifiee.',
                'source' => 'Angular Blog',
                'publishedAt' => new \DateTimeImmutable('-10 hours')
            ],
            [
                'title' => 'PHP 8.4 : Property Hooks revolutionne les accesseurs',
                'url' => 'https://www.php.net/releases/8.4/',
                'description' => 'PHP 8.4 introduit les Property Hooks, permettant de definir des getters/setters directement dans les proprietes.',
                'source' => 'PHP.net',
                'publishedAt' => new \DateTimeImmutable('-12 hours')
            ],
            [
                'title' => 'Git 2.44 : Ameliorations de performance sur les gros repos',
                'url' => 'https://github.blog/changelog/2024-git-2-44',
                'description' => 'Git 2.44 optimise les operations sur les tres gros repositories avec un nouvel algorithme de compression.',
                'source' => 'GitHub Blog',
                'publishedAt' => new \DateTimeImmutable('-15 hours')
            ],
            [
                'title' => 'Bootstrap 5.4 : Nouveaux composants et Dark Mode ameliore',
                'url' => 'https://blog.getbootstrap.com/2024/bootstrap-5-4/',
                'description' => 'Bootstrap 5.4 ajoute de nouveaux composants et ameliore la gestion du Dark Mode avec des variables CSS.',
                'source' => 'Bootstrap Blog',
                'publishedAt' => new \DateTimeImmutable('-18 hours')
            ],
            [
                'title' => 'Tailwind CSS 4.0 : Engine CSS natif ultra-rapide',
                'url' => 'https://tailwindcss.com/blog/tailwindcss-v4',
                'description' => 'Tailwind CSS 4.0 remplace PostCSS par un moteur natif en Rust, divisant les temps de build par 10.',
                'source' => 'Tailwind Blog',
                'publishedAt' => new \DateTimeImmutable('-20 hours')
            ],
            [
                'title' => 'JavaScript : Les nouveautes ECMAScript 2025',
                'url' => 'https://tc39.es/ecma262/2025/',
                'description' => 'ECMAScript 2025 apporte des decorators standardises, le pattern matching et les records/tuples.',
                'source' => 'TC39 Blog',
                'publishedAt' => new \DateTimeImmutable('-1 day')
            ],
            [
                'title' => 'TypeScript 5.6 : Inference de types amelioree',
                'url' => 'https://devblogs.microsoft.com/typescript/announcing-typescript-5-6/',
                'description' => 'TypeScript 5.6 ameliore l\'inference de types dans les generics et reduit les erreurs de compilation.',
                'source' => 'Microsoft DevBlogs',
                'publishedAt' => new \DateTimeImmutable('-1 day -2 hours')
            ],
            [
                'title' => 'Node.js 22 LTS : Support natif des modules ES6',
                'url' => 'https://nodejs.org/en/blog/release/v22',
                'description' => 'Node.js 22 devient LTS avec un support complet des modules ES6 sans flags experimentaux.',
                'source' => 'Node.js Blog',
                'publishedAt' => new \DateTimeImmutable('-1 day -5 hours')
            ],
            [
                'title' => 'Vite 6.0 : Build ultra-rapide pour tous les frameworks',
                'url' => 'https://vitejs.dev/blog/announcing-vite6',
                'description' => 'Vite 6.0 optimise le build et supporte nativement React, Vue, Svelte et Angular.',
                'source' => 'Vite Blog',
                'publishedAt' => new \DateTimeImmutable('-1 day -8 hours')
            ],
            [
                'title' => 'React 19 : Nouveau compilateur et Server Components',
                'url' => 'https://react.dev/blog/2024/react-19',
                'description' => 'React 19 introduit un compilateur optimisant et les Server Components en production.',
                'source' => 'React Blog',
                'publishedAt' => new \DateTimeImmutable('-1 day -12 hours')
            ],
            [
                'title' => 'Vue.js 3.5 : Reactive Props et meilleure DX',
                'url' => 'https://blog.vuejs.org/posts/vue-3-5',
                'description' => 'Vue.js 3.5 ameliore la reactivite des props et offre de meilleurs outils de debugging.',
                'source' => 'Vue.js Blog',
                'publishedAt' => new \DateTimeImmutable('-1 day -15 hours')
            ],
            [
                'title' => 'Svelte 5 : Runes revolutionne la reactivite',
                'url' => 'https://svelte.dev/blog/svelte-5',
                'description' => 'Svelte 5 introduit les Runes, un nouveau systeme de reactivite plus puissant et explicite.',
                'source' => 'Svelte Blog',
                'publishedAt' => new \DateTimeImmutable('-2 days')
            ],
            [
                'title' => 'Next.js 15 : App Router stabilise et Turbopack par defaut',
                'url' => 'https://nextjs.org/blog/next-15',
                'description' => 'Next.js 15 marque la stabilisation de l\'App Router et active Turbopack pour des builds ultra-rapides.',
                'source' => 'Vercel Blog',
                'publishedAt' => new \DateTimeImmutable('-2 days -4 hours')
            ],
            [
                'title' => 'Nuxt 4.0 : Architecture modulaire et meilleure performance',
                'url' => 'https://nuxt.com/blog/v4',
                'description' => 'Nuxt 4.0 repense l\'architecture modulaire et ameliore les performances SSR de 30%.',
                'source' => 'Nuxt Blog',
                'publishedAt' => new \DateTimeImmutable('-2 days -8 hours')
            ],
            [
                'title' => 'Astro 4.5 : Content Collections et Islands optimises',
                'url' => 'https://astro.build/blog/astro-450/',
                'description' => 'Astro 4.5 ameliore les Content Collections et optimise le hydratation des Islands.',
                'source' => 'Astro Blog',
                'publishedAt' => new \DateTimeImmutable('-2 days -12 hours')
            ],
            [
                'title' => 'Deno 2.0 : Compatibilite NPM totale et performances record',
                'url' => 'https://deno.com/blog/v2',
                'description' => 'Deno 2.0 atteint une compatibilite NPM complete tout en gardant ses performances superieures.',
                'source' => 'Deno Blog',
                'publishedAt' => new \DateTimeImmutable('-3 days')
            ],

            // Articles plus anciens (page 2)
            [
                'title' => 'Bun 1.1 : Le runtime JavaScript le plus rapide',
                'url' => 'https://bun.sh/blog/bun-v1.1',
                'description' => 'Bun 1.1 confirme sa position de runtime JavaScript le plus rapide avec de nouvelles optimisations.',
                'source' => 'Bun Blog',
                'publishedAt' => new \DateTimeImmutable('-3 days -6 hours')
            ],
            [
                'title' => 'Prisma 6.0 : ORM next-gen avec TypeScript natif',
                'url' => 'https://www.prisma.io/blog/prisma-6',
                'description' => 'Prisma 6.0 repense l\'ORM avec TypeScript natif et des requetes 50% plus rapides.',
                'source' => 'Prisma Blog',
                'publishedAt' => new \DateTimeImmutable('-4 days')
            ],
            [
                'title' => 'Drizzle ORM : L\'alternative legere a Prisma',
                'url' => 'https://orm.drizzle.team/blog',
                'description' => 'Drizzle ORM se positionne comme une alternative legere et performante a Prisma.',
                'source' => 'Drizzle Blog',
                'publishedAt' => new \DateTimeImmutable('-4 days -8 hours')
            ],
            [
                'title' => 'Supabase : Backend-as-a-Service open source en plein essor',
                'url' => 'https://supabase.com/blog',
                'description' => 'Supabase continue sa croissance comme alternative open source a Firebase.',
                'source' => 'Supabase Blog',
                'publishedAt' => new \DateTimeImmutable('-5 days')
            ],
            [
                'title' => 'Turborepo : Monorepos haute performance pour JavaScript',
                'url' => 'https://turbo.build/blog',
                'description' => 'Turborepo revolutionne la gestion des monorepos avec un cache intelligent.',
                'source' => 'Vercel Blog',
                'publishedAt' => new \DateTimeImmutable('-5 days -6 hours')
            ],
            [
                'title' => 'pnpm 9.0 : Gestionnaire de paquets ultra-efficace',
                'url' => 'https://pnpm.io/blog/2024/pnpm-9',
                'description' => 'pnpm 9.0 economise encore plus d\'espace disque et accelere les installations.',
                'source' => 'pnpm Blog',
                'publishedAt' => new \DateTimeImmutable('-6 days')
            ],
            [
                'title' => 'Vitest 2.0 : Tests unitaires ultra-rapides pour Vite',
                'url' => 'https://vitest.dev/blog/vitest-2-0',
                'description' => 'Vitest 2.0 devient le standard pour tester les applications Vite avec une vitesse record.',
                'source' => 'Vitest Blog',
                'publishedAt' => new \DateTimeImmutable('-6 days -8 hours')
            ],
            [
                'title' => 'Playwright 1.45 : Tests E2E avec IA integree',
                'url' => 'https://playwright.dev/blog/playwright-1-45',
                'description' => 'Playwright 1.45 integre l\'IA pour generer automatiquement des tests E2E.',
                'source' => 'Playwright Blog',
                'publishedAt' => new \DateTimeImmutable('-7 days')
            ],
            [
                'title' => 'Cypress 14 : Component Testing ameliore',
                'url' => 'https://www.cypress.io/blog/2024/cypress-14',
                'description' => 'Cypress 14 ameliore le Component Testing avec un support natif de tous les frameworks.',
                'source' => 'Cypress Blog',
                'publishedAt' => new \DateTimeImmutable('-7 days -10 hours')
            ],
            [
                'title' => 'Storybook 8.0 : Documentation interactive de composants',
                'url' => 'https://storybook.js.org/blog/storybook-8/',
                'description' => 'Storybook 8.0 repense l\'interface et accelere le chargement des stories.',
                'source' => 'Storybook Blog',
                'publishedAt' => new \DateTimeImmutable('-8 days')
            ],
        ];
    }
}