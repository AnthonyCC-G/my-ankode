<?php

namespace App\Command;

use App\Entity\User;
use App\Service\RssFeedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fetch-rss',
    description: 'Récupère et stocke les articles depuis un flux RSS',
)]
class FetchRssCommand extends Command
{
    public function __construct(
        private RssFeedService $rssFeedService,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'URL du flux RSS')
            ->addArgument('source', InputArgument::REQUIRED, 'Nom de la source (ex: Dev.to)')
            ->setHelp(
                'Cette commande récupère les articles depuis un flux RSS et les stocke dans MongoDB.' . PHP_EOL .
                'Exemple : php bin/console app:fetch-rss https://dev.to/feed "Dev.to"'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupération des arguments
        $url = $input->getArgument('url');
        $source = $input->getArgument('source');

        // Récupération du premier utilisateur
        $user = $this->em->getRepository(User::class)->findOneBy([]);

        if (!$user) {
            $io->error('Aucun utilisateur trouvé en base de données. Crée un compte d\'abord !');
            return Command::FAILURE;
        }

        $io->title('Récupération du flux RSS');
        $io->info("URL : $url");
        $io->info("Source : $source");
        $io->info("Utilisateur : {$user->getEmail()}");
        $io->newLine();

        // Appel du service RSS
        $io->text('Téléchargement et parsing du flux...');
        $result = $this->rssFeedService->fetchFeed($url, $user, $source);

        // Affichage du résultat
        if ($result['success']) {
            $io->success([
                "Flux RSS récupéré avec succès !",
                "{$result['count']} article(s) importé(s) dans MongoDB"
            ]);
            return Command::SUCCESS;
        } else {
            $io->error([
                'Erreur lors de la récupération du flux RSS',
                "Détails : {$result['error']}"
            ]);
            return Command::FAILURE;
        }
    }
}