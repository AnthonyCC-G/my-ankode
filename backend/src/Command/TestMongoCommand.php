<?php

namespace App\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-mongo',
    description: 'Test MongoDB connection and list collections',
)]
class TestMongoCommand extends Command
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
            // 1️⃣ Test de connexion
            $io->section('🔌 Test de connexion MongoDB');
            
            $client = $this->documentManager->getClient();
            $databases = iterator_to_array($client->listDatabases());
            
            $io->success('✅ Connexion MongoDB réussie !');
            
            // 2️⃣ Afficher la base de données utilisée
            $io->section('🗄️ Base de données');
            $configuration = $this->documentManager->getConfiguration();
            $dbName = $configuration->getDefaultDB();
            $io->text('Nom : ' . $dbName);

            // Accès direct à la base
            $database = $client->selectDatabase($dbName);

            // 3️⃣ Lister les collections existantes
            $io->section('📂 Collections existantes');
            $collections = iterator_to_array($database->listCollections());
            
            if (empty($collections)) {
                $io->warning('Aucune collection trouvée (c\'est normal au début)');
            } else {
                foreach ($collections as $collection) {
                    $io->text('- ' . $collection->getName());
                }
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('❌ Erreur de connexion : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}