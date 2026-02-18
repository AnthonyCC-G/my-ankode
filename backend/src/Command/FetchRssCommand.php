<?php

/**
 * FETCHRSSCOMMAND.PHP - Commande CLI pour importer les flux RSS dans MongoDB
 * 
 * Responsabilités :
 * - Récupérer un flux RSS depuis une URL fournie en argument
 * - Parser le contenu XML du flux RSS
 * - Sauvegarder les articles dans MongoDB (collection 'articles')
 * - Éviter les doublons (vérification par URL)
 * - Afficher le résultat de l'import dans le terminal
 * 
 * Architecture :
 * - Commande Symfony Console (extends Command)
 * - Attribut #[AsCommand] pour enregistrement automatique
 * - Injection du RssFeedService via constructeur (délégation de la logique métier)
 * - Arguments requis : url (flux RSS) + source (nom de la source)
 * - Utilise SymfonyStyle pour affichage formaté
 * 
 * Workflow d'exécution :
 * 1. configure() : définition des arguments (url, source)
 * 2. execute() : récupération des arguments + appel du service
 * 3. RssFeedService : téléchargement, parsing, sauvegarde
 * 4. Affichage du résultat (succès ou erreur)
 * 5. Retour du code de sortie (SUCCESS ou FAILURE)
 * 
 * Arguments obligatoires :
 * - url : URL complète du flux RSS (ex: https://korben.info/feed)
 * - source : Nom affiché dans l'interface (ex: "Korben")
 * 
 * Utilité :
 * - Import initial : charger les premiers articles
 * - Mise à jour régulière : cron job quotidien
 * - Test : vérifier qu'un nouveau flux RSS fonctionne
 * 
 * Exécution manuelle :
 * - php bin/console app:fetch-rss https://korben.info/feed "Korben"
 * - php bin/console app:fetch-rss https://dev.to/feed "Dev.to"
 * 
 * Exécution automatique (cron) :
 * - 0 8 * * * cd /path/to/app && php bin/console app:fetch-rss https://korben.info/feed "Korben"
 * - Tous les jours à 8h : import des nouveaux articles
 * 
 * Articles publics (userId = null) :
 * - Tous les articles RSS sont partagés entre utilisateurs
 * - Pas de propriétaire spécifique
 * - Chaque utilisateur peut marquer comme lu/favori individuellement
 * 
* CP7 - Composants d'accès aux données
 * - Séparation Service/Command : bonne pratique architecture (testabilité, réutilisabilité)
 * - Gestion des erreurs avec codes de retour shell appropriés
 */

namespace App\Command;

use App\Service\RssFeedService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// ===== 1. ATTRIBUT DE CONFIGURATION - ENREGISTREMENT DE LA COMMANDE =====

// Attribut PHP 8+ pour déclarer la commande Symfony
// - name: nom utilisé dans le terminal (php bin/console app:fetch-rss)
// - description: texte affiché dans la liste des commandes (php bin/console list)
#[AsCommand(
    name: 'app:fetch-rss',
    description: 'Récupère et stocke les articles depuis un flux RSS',
)]
class FetchRssCommand extends Command
{
    // ===== 2. INJECTION DE DÉPENDANCES - RSS FEED SERVICE =====
    
    public function __construct(
        private RssFeedService $rssFeedService  // Service métier injecté automatiquement par Symfony
    ) {
        // Appel obligatoire au constructeur parent Command
        // Initialise les propriétés internes de la commande Symfony
        parent::__construct();
    }

    // ===== 3. CONFIGURATION - DÉFINITION DES ARGUMENTS DE LA COMMANDE =====
    
    protected function configure(): void
    {
        // Configuration des arguments et de l'aide de la commande
        //
        // Arguments Symfony Console :
        // - InputArgument::REQUIRED : argument obligatoire (commande échoue si absent)
        // - InputArgument::OPTIONAL : argument facultatif (valeur par défaut possible)
        // - InputArgument::IS_ARRAY : argument multiple (php bin/console cmd arg1 arg2 arg3)
        //
        // Méthodes chaînées :
        // - addArgument() : ajouter un argument à la commande
        // - setHelp() : définir le texte d'aide affiché avec --help
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'URL du flux RSS')
            ->addArgument('source', InputArgument::REQUIRED, 'Nom de la source (ex: Dev.to)')
            ->setHelp(
                // PHP_EOL : constante PHP pour saut de ligne (\n sur Linux, \r\n sur Windows)
                // Permet de créer une aide multi-lignes
                'Cette commande récupère les articles depuis un flux RSS et les stocke dans MongoDB.' . PHP_EOL .
                'Exemple : php bin/console app:fetch-rss https://dev.to/feed "Dev.to"'
            );
    }

    // ===== 4. MÉTHODE PRINCIPALE - LOGIQUE D'EXÉCUTION DE LA COMMANDE =====
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ===== 4a. INITIALISATION SYMFONY STYLE - AFFICHAGE FORMATÉ =====
        
        // SymfonyStyle : helper Symfony pour affichage CLI avec couleurs, tableaux, etc.
        // $input : contient les arguments et options passés à la commande
        // $output : interface pour écrire dans le terminal
        $io = new SymfonyStyle($input, $output);

        // ===== 4b. RÉCUPÉRATION DES ARGUMENTS DE LA COMMANDE =====
        
        // Récupération des arguments passés dans le terminal
        //
        // Exemples :
        // - php bin/console app:fetch-rss https://korben.info/feed "Korben"
        //   → $url = "https://korben.info/feed"
        //   → $source = "Korben"
        //
        // getArgument() : méthode Symfony Console pour récupérer un argument par son nom
        $url = $input->getArgument('url');
        $source = $input->getArgument('source');

        // Les articles RSS sont PUBLICS (userId = null)

        // ===== 4c. AFFICHAGE DES INFORMATIONS DE L'IMPORT =====
        
        // Affichage du titre avec formatage SymfonyStyle
        // title() : affiche un titre encadré avec séparateurs
        $io->title('Récupération du flux RSS PUBLIC');
        
        // info() : affiche un bloc bleu avec icône ℹ
        // Affichage des paramètres de l'import pour confirmation visuelle
        $io->info("URL : $url");
        $io->info("Source : $source");
        
        // note() : affiche un bloc jaune avec icône !
        // Rappel important : ces articles seront visibles par tous les utilisateurs
        $io->note('Ces articles seront visibles par TOUS les utilisateurs');
        
        // Saut de ligne pour lisibilité
        $io->newLine();

        // ===== 4d. APPEL DU SERVICE RSS - DÉLÉGATION DE LA LOGIQUE MÉTIER =====
        
        // Appel du service RSS avec null (articles publics)
        //
        // POURQUOI DÉLÉGUER À UN SERVICE ?
        // - Séparation des responsabilités (Command = CLI, Service = logique métier)
        // - Testabilité : on peut tester RssFeedService sans CLI
        // - Réutilisabilité : le service peut être appelé depuis un controller API
        //
        // Paramètres de fetchFeed() :
        // - $url : URL du flux RSS à télécharger
        // - null : pas de propriétaire (userId = null, articles publics)
        // - $source : nom de la source pour identification
        //
        // text() : affiche du texte simple sans formatage spécial
        $io->text('Téléchargement et parsing du flux...');
        
        // Appel du service RssFeedService injecté dans le constructeur
        // fetchFeed() retourne un tableau : ['success' => bool, 'count' => int, 'error' => string|null]
        $result = $this->rssFeedService->fetchFeed($url, null, $source);

        // ===== 4e. AFFICHAGE DU RÉSULTAT - SUCCÈS OU ÉCHEC =====
        
        // Affichage du résultat selon le succès ou l'échec de l'import
        //
        // Structure du tableau $result :
        // - Si succès : ['success' => true, 'count' => 15]
        // - Si échec : ['success' => false, 'count' => 0, 'error' => "Erreur HTTP 404"]
        if ($result['success']) {
            // ===== CAS SUCCÈS : AFFICHAGE DU NOMBRE D'ARTICLES IMPORTÉS =====
            
            // success() : affiche un bloc vert avec icône ✔
            // Accepte un string OU un array de strings (plusieurs lignes)
            $io->success([
                "Flux RSS récupéré avec succès !",
                "{$result['count']} article(s) public(s) importé(s) dans MongoDB"
            ]);
            
            // Command::SUCCESS : constante = 0 (code de retour shell pour succès)
            // Utilisé par les scripts bash/cron pour détecter le succès
            return Command::SUCCESS;
        } else {
            // ===== CAS ÉCHEC : AFFICHAGE DE L'ERREUR =====
            
            // error() : affiche un bloc rouge avec icône ✖
            // Affiche le message d'erreur retourné par le service
            $io->error([
                'Erreur lors de la récupération du flux RSS',
                "Détails : {$result['error']}"
            ]);
            
            // Command::FAILURE : constante = 1 (code de retour shell pour échec)
            // Utilisé par les scripts bash/cron pour détecter l'échec et alerter
            return Command::FAILURE;
        }
    }
}