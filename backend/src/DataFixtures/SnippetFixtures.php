<?php

namespace App\DataFixtures;

use App\Document\Snippet;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;

class SnippetFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private DocumentManager $dm,
        private EntityManagerInterface $em  // AJOUTER
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Récupérer les users DIRECTEMENT depuis PostgreSQL
        $userRepository = $this->em->getRepository(User::class);
        $anthony = $userRepository->findOneBy(['username' => 'anthony_dev']);
        $alice = $userRepository->findOneBy(['username' => 'alice_user']);
        $marie = $userRepository->findOneBy(['username' => 'marie_user']);

        // Vérifier que les users existent
        if (!$anthony || !$alice || !$marie) {
            throw new \Exception('Users not found! Load UserFixtures first.');
        }

        // ========================================
        // SNIPPETS POUR ANTHONY (3 snippets)
        // ========================================
        
        $snippet1 = new Snippet();
        $snippet1->setUserId($anthony->getId());
        $snippet1->setTitle('Docker Compose restart');
        $snippet1->setLanguage('other');
        $snippet1->setCode('docker-compose restart && docker exec myankode-backend php bin/console cache:clear');
        $snippet1->setDescription('Commande pour redémarrer Docker et vider le cache Symfony');
        $snippet1->setTags(['docker', 'symfony', 'devops']);
        $this->dm->persist($snippet1);

        $snippet2 = new Snippet();
        $snippet2->setUserId($anthony->getId());
        $snippet2->setTitle('Query MongoDB userId');
        $snippet2->setLanguage('php');
        $snippet2->setCode('$this->createQueryBuilder()->field(\'userId\')->equals($user->getId())->getQuery()->execute();');
        $snippet2->setDescription('Requête MongoDB Doctrine ODM pour filtrer par userId');
        $snippet2->setTags(['mongodb', 'doctrine', 'odm']);
        $this->dm->persist($snippet2);

        $snippet3 = new Snippet();
        $snippet3->setUserId($anthony->getId());
        $snippet3->setTitle('Twig truncate title');
        $snippet3->setLanguage('other');
        $snippet3->setCode('{{ snippet.title|length > 15 ? snippet.title|slice(0, 15) ~ \'...\' : snippet.title }}');
        $snippet3->setDescription('Tronquer un titre à 15 caractères avec ellipse');
        $snippet3->setTags(['twig', 'template', 'ui']);
        $this->dm->persist($snippet3);

        // ========================================
        // SNIPPETS POUR ALICE (4 snippets)
        // ========================================
        
        $snippet4 = new Snippet();
        $snippet4->setUserId($alice->getId());
        $snippet4->setTitle('Valider un email PHP');
        $snippet4->setLanguage('php');
        $snippet4->setCode('if (filter_var($email, FILTER_VALIDATE_EMAIL)) { return true; }');
        $snippet4->setDescription('Fonction simple pour valider un email en PHP');
        $snippet4->setTags(['validation', 'email', 'php']);
        $this->dm->persist($snippet4);

        $snippet5 = new Snippet();
        $snippet5->setUserId($alice->getId());
        $snippet5->setTitle('Debounce function');
        $snippet5->setLanguage('javascript');
        $snippet5->setCode('const debounce = (func, delay) => { let timeout; return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => func(...args), delay); }; };');
        $snippet5->setDescription('Fonction debounce pour optimiser les appels');
        $snippet5->setTags(['performance', 'javascript', 'utils']);
        $this->dm->persist($snippet5);

        $snippet6 = new Snippet();
        $snippet6->setUserId($alice->getId());
        $snippet6->setTitle('Flexbox center');
        $snippet6->setLanguage('css');
        $snippet6->setCode('.container { display: flex; justify-content: center; align-items: center; min-height: 100vh; }');
        $snippet6->setDescription('Centrer un élément avec flexbox');
        $snippet6->setTags(['css', 'flexbox', 'layout']);
        $this->dm->persist($snippet6);

        $snippet7 = new Snippet();
        $snippet7->setUserId($alice->getId());
        $snippet7->setTitle('JOIN avec COUNT');
        $snippet7->setLanguage('sql');
        $snippet7->setCode('SELECT u.name, COUNT(p.id) as total FROM users u LEFT JOIN projects p ON u.id = p.owner_id GROUP BY u.id;');
        $snippet7->setDescription('Compter les projets par utilisateur');
        $snippet7->setTags(['sql', 'jointure', 'count']);
        $this->dm->persist($snippet7);

        // ========================================
        // SNIPPETS POUR MARIE (4 snippets)
        // ========================================
        
        $snippet8 = new Snippet();
        $snippet8->setUserId($marie->getId());
        $snippet8->setTitle('Array map en JS');
        $snippet8->setLanguage('javascript');
        $snippet8->setCode('const doubled = numbers.map(n => n * 2);');
        $snippet8->setDescription('Transformer un tableau avec map');
        $snippet8->setTags(['javascript', 'array', 'functional']);
        $this->dm->persist($snippet8);

        $snippet9 = new Snippet();
        $snippet9->setUserId($marie->getId());
        $snippet9->setTitle('Route Symfony GET');
        $snippet9->setLanguage('php');
        $snippet9->setCode('#[Route(\'/api/items\', name: \'api_items_list\', methods: [\'GET\'])]');
        $snippet9->setDescription('Déclaration route Symfony avec attribut PHP 8');
        $snippet9->setTags(['symfony', 'routing', 'api']);
        $this->dm->persist($snippet9);

        $snippet10 = new Snippet();
        $snippet10->setUserId($marie->getId());
        $snippet10->setTitle('Grid responsive 3 cols');
        $snippet10->setLanguage('css');
        $snippet10->setCode('.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }');
        $snippet10->setDescription('Grille CSS responsive avec auto-fit');
        $snippet10->setTags(['css', 'grid', 'responsive']);
        $this->dm->persist($snippet10);

        $snippet11 = new Snippet();
        $snippet11->setUserId($marie->getId());
        $snippet11->setTitle('HTML form template');
        $snippet11->setLanguage('html');
        $snippet11->setCode('<form method="POST" action="/submit"><input type="text" name="title" required><button type="submit">Envoyer</button></form>');
        $snippet11->setDescription('Template formulaire HTML basique');
        $snippet11->setTags(['html', 'form', 'template']);
        $this->dm->persist($snippet11);

        // Flush MongoDB EXPLICITEMENT
        $this->dm->flush();
        
        echo "✅ " . ($this->dm->getRepository(Snippet::class)->createQueryBuilder()->count()->getQuery()->execute()) . " snippets chargés dans MongoDB\n";
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}