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
    name: 'app:count-articles',
    description: 'Compte le nombre d\'articles en base MongoDB',
)]
class CountArticlesCommand extends Command
{
    public function __construct(
        private DocumentManager $dm
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Compter le total
        $total = $this->dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null)
            ->count()
            ->getQuery()
            ->execute();
        
        // Compter par source
        $sources = $this->dm->createQueryBuilder(Article::class)
            ->distinct('source')
            ->field('userId')->equals(null)
            ->getQuery()
            ->execute();
        
        $io->title(' Statistiques Articles RSS');
        $io->writeln("Total articles : <info>{$total}</info>");
        $io->newLine();
        
        $sources = is_array($sources) ? $sources : iterator_to_array($sources);
        
        foreach ($sources as $source) {
            $count = $this->dm->createQueryBuilder(Article::class)
                ->field('userId')->equals(null)
                ->field('source')->equals($source)
                ->count()
                ->getQuery()
                ->execute();
            
            $io->writeln("  • {$source} : <comment>{$count}</comment>");
        }
        
        $io->success('Comptage terminé !');
        
        return Command::SUCCESS;
    }
}