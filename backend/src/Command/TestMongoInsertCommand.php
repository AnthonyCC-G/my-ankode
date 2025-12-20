<?php

namespace App\Command;

use App\Document\Snippet;
use App\Document\Article;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-mongo-insert',
    description: 'Insert test data in MongoDB (Snippet + Article)',
)]
class TestMongoInsertCommand extends Command
{
    public function __construct(
        private DocumentManager $documentManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // 1️⃣ Créer un Snippet de test
            $io->section('📝 Création d\'un Snippet de test');
            
            $snippet = new Snippet();
            $snippet->setTitle('Connexion PostgreSQL en PHP');
            $snippet->setLanguage('PHP');
            $snippet->setCode('$pdo = new PDO("pgsql:host=localhost;dbname=test", "user", "pass");');
            $snippet->setDescription('Exemple de connexion à PostgreSQL avec PDO');
            $snippet->setTags(['PHP', 'PostgreSQL', 'PDO', 'Database']);
            
            $this->documentManager->persist($snippet);
            $this->documentManager->flush();
            
            $io->success('✅ Snippet créé avec l\'ID : ' . $snippet->getId());
            $io->text('Titre : ' . $snippet->getTitle());
            $io->text('Langage : ' . $snippet->getLanguage());
            $io->text('Tags : ' . implode(', ', $snippet->getTags()));

            // 2️⃣ Créer un Article de test
            $io->section('📰 Création d\'un Article de test');
            
            $article = new Article();
            $article->setTitle('Les nouveautés de Symfony 7');
            $article->setUrl('https://symfony.com/blog/symfony-7-0-released');
            $article->setDescription('Symfony 7.0 apporte de nombreuses améliorations et nouvelles fonctionnalités');
            $article->setSource('Symfony Blog');
            $article->setTags(['Symfony', 'PHP', 'Framework']);
            $article->setPublishedAt(new \DateTimeImmutable('2023-11-30'));
            $article->setIsRead(false);
            
            $this->documentManager->persist($article);
            $this->documentManager->flush();
            
            $io->success('✅ Article créé avec l\'ID : ' . $article->getId());
            $io->text('Titre : ' . $article->getTitle());
            $io->text('Source : ' . $article->getSource());
            $io->text('URL : ' . $article->getUrl());
            $io->text('Tags : ' . implode(', ', $article->getTags()));

            // 3️⃣ Récapitulatif
            $io->section('📊 Récapitulatif');
            $io->text('Nombre de Snippets en base : ' . 
                $this->documentManager->getRepository(Snippet::class)->createQueryBuilder()->count()->getQuery()->execute()
            );
            $io->text('Nombre d\'Articles en base : ' . 
                $this->documentManager->getRepository(Article::class)->createQueryBuilder()->count()->getQuery()->execute()
            );

            $io->note('💡 Astuce : Lance "php bin/console app:test-mongo" pour vérifier que les collections ont été créées !');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'insertion : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}