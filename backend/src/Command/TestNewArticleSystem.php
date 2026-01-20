<?php

namespace App\Command;

use App\Document\Article;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-new-article-system',
    description: 'Teste le nouveau système d\'articles publics',
)]
class TestNewArticleSystem extends Command
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
            $io->section('📰 Création d\'articles PUBLICS (userId = null)');
            
            // Article 1
            $article1 = new Article();
            $article1->setTitle('Les nouveautés de Symfony 7.2');
            $article1->setUrl('https://symfony.com/blog/symfony-7-2');
            $article1->setDescription('Découvrez les nouvelles fonctionnalités de Symfony 7.2');
            $article1->setSource('Symfony Blog');
            $article1->setTags(['Symfony', 'PHP', 'Framework']);
            $article1->setPublishedAt(new \DateTimeImmutable('2026-01-15'));
            $article1->setUserId(null);  // ← ARTICLE PUBLIC
            
            $this->documentManager->persist($article1);
            
            // Article 2
            $article2 = new Article();
            $article2->setTitle('Docker Compose : Astuces avancées');
            $article2->setUrl('https://korben.info/docker-compose-tips');
            $article2->setDescription('10 astuces pour optimiser vos fichiers docker-compose');
            $article2->setSource('Korben.info');
            $article2->setTags(['Docker', 'DevOps', 'Containers']);
            $article2->setPublishedAt(new \DateTimeImmutable('2026-01-18'));
            $article2->setUserId(null);  // ← ARTICLE PUBLIC
            
            $this->documentManager->persist($article2);
            
            // Article 3
            $article3 = new Article();
            $article3->setTitle('MongoDB vs PostgreSQL : Quel choix ?');
            $article3->setUrl('https://dev.to/mongodb-vs-postgresql');
            $article3->setDescription('Comparaison des deux bases de données');
            $article3->setSource('Dev.to');
            $article3->setTags(['MongoDB', 'PostgreSQL', 'Database']);
            $article3->setPublishedAt(new \DateTimeImmutable('2026-01-19'));
            $article3->setUserId(null);  // ← ARTICLE PUBLIC
            
            $this->documentManager->persist($article3);
            
            $this->documentManager->flush();
            
            $io->success('✅ 3 articles publics créés avec succès !');
            
            $io->section('📊 Vérification');
            $io->text('Article 1 ID : ' . $article1->getId());
            $io->text('Article 2 ID : ' . $article2->getId());
            $io->text('Article 3 ID : ' . $article3->getId());
            
            $io->note('Ces articles sont visibles par TOUS les utilisateurs');
            $io->note('Chaque user peut avoir ses propres favoris/lectures');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}