<?php

/**
 * COUNTARTICLESCOMMAND.PHP - Commande CLI pour compter les articles RSS
 * 
 * Responsabilités :
 * - Compter le nombre total d'articles publics dans MongoDB
 * - Compter le nombre d'articles par source (Korben, Dev.to, etc.)
 * - Afficher les statistiques dans le terminal de manière formatée
 * - Exécution manuelle via : php bin/console app:count-articles
 * 
 * Architecture :
 * - Commande Symfony Console (extends Command)
 * - Attribut #[AsCommand] pour enregistrement automatique
 * - Injection du DocumentManager MongoDB via constructeur
 * - Utilise SymfonyStyle pour affichage formaté (couleurs, tableaux)
 * 
 * Workflow d'exécution :
 * 1. Construction : injection des dépendances
 * 2. execute() : logique principale
 * 3. Requête MongoDB : comptage total des articles publics
 * 4. Requête MongoDB : liste des sources distinctes
 * 5. Boucle : comptage par source
 * 6. Affichage formaté avec SymfonyStyle
 * 7. Retour SUCCESS ou FAILURE
 * 
 * Utilité :
 * - Debug : vérifier que le FetchRssCommand a bien importé les articles
 * - Monitoring : statistiques rapides sans interface web
 * - Admin : vérification de la répartition des sources
 * 
 * Exécution :
 * - Manuelle : php bin/console app:count-articles
 * - Potentiellement scriptable dans un monitoring système
 * 
 * - CP7 - Développer des composants d'accès aux données
 * - Utilisation avancée de MongoDB avec QueryBuilder
 * - Gestion de la présentation CLI (bonnes pratiques UX terminal)
 */

namespace App\Command;

use App\Document\Article;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// ===== 1. ATTRIBUT DE CONFIGURATION - ENREGISTREMENT DE LA COMMANDE =====

// Attribut PHP 8+ pour déclarer la commande Symfony
// - name: nom utilisé dans le terminal (php bin/console app:count-articles)
// - description: texte affiché dans la liste des commandes (php bin/console list)
#[AsCommand(
    name: 'app:count-articles',
    description: 'Compte le nombre d\'articles en base MongoDB',
)]
class CountArticlesCommand extends Command
{
    // ===== 2. INJECTION DE DÉPENDANCES - DOCUMENT MANAGER MONGODB =====
    
    public function __construct(
        private DocumentManager $dm  // DocumentManager injecté automatiquement par Symfony
    ) {
        // Appel obligatoire au constructeur parent Command
        // Initialise les propriétés internes de la commande Symfony
        parent::__construct();
    }

    // ===== 3. MÉTHODE PRINCIPALE - LOGIQUE D'EXÉCUTION DE LA COMMANDE =====
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ===== 3a. INITIALISATION SYMFONY STYLE - AFFICHAGE FORMATÉ =====
        
        // SymfonyStyle : helper Symfony pour affichage CLI avec couleurs, tableaux, etc.
        // $input : arguments et options de la commande (ici aucun)
        // $output : interface pour écrire dans le terminal
        $io = new SymfonyStyle($input, $output);
        
        // ===== 3b. REQUÊTE MONGODB - COMPTAGE TOTAL DES ARTICLES PUBLICS =====
        
        // Compter le total des articles publics (userId = null)
        // 
        // Requête MongoDB équivalente :
        // db.articles.countDocuments({ userId: null })
        //
        // QueryBuilder Doctrine ODM :
        // - createQueryBuilder(Article::class) : crée un builder pour la collection 'articles'
        // - field('userId')->equals(null) : filtre WHERE userId = null
        // - count() : transforme la requête en COUNT au lieu de FIND
        // - getQuery() : finalise le builder en Query exécutable
        // - execute() : exécute la requête et retourne le résultat (int)
        $total = $this->dm->createQueryBuilder(Article::class)
            ->field('userId')->equals(null)  // Filtre : articles publics uniquement
            ->count()                         // Transformation : COUNT au lieu de SELECT
            ->getQuery()                      // Construction de la Query finale
            ->execute();                      // Exécution et récupération du résultat
        
        // ===== 3c. REQUÊTE MONGODB - LISTE DES SOURCES DISTINCTES =====
        
        // Compter par source (récupérer toutes les sources uniques)
        //
        // Requête MongoDB équivalente :
        // db.articles.distinct('source', { userId: null })
        //
        // QueryBuilder Doctrine ODM :
        // - distinct('source') : SELECT DISTINCT source (valeurs uniques)
        // - field('userId')->equals(null) : filtre WHERE userId = null
        // - execute() : retourne un array ou un Iterator (selon version Doctrine)
        $sources = $this->dm->createQueryBuilder(Article::class)
            ->distinct('source')              // SELECT DISTINCT source
            ->field('userId')->equals(null)   // Filtre : articles publics uniquement
            ->getQuery()                      // Construction de la Query finale
            ->execute();                      // Exécution (retourne array ou Iterator)
        
        // ===== 3d. AFFICHAGE DES STATISTIQUES GLOBALES =====
        
        // Affichage du titre avec formatage SymfonyStyle
        // title() : affiche un titre encadré avec séparateurs
        $io->title(' Statistiques Articles RSS');
        
        // Affichage du total avec balise <info> pour couleur verte
        // <info>...</info> : couleur verte dans le terminal
        $io->writeln("Total articles : <info>{$total}</info>");
        
        // Saut de ligne pour lisibilité
        $io->newLine();
        
        // ===== 3e. NORMALISATION DU RÉSULTAT SOURCES - COMPATIBILITÉ DOCTRINE =====
        
        // Convertir le résultat en array si c'est un Iterator
        // 
        // POURQUOI ?
        // - Doctrine ODM peut retourner un array OU un Iterator selon la version
        // - foreach() fonctionne sur les deux, MAIS on veut garantir un array
        // - is_array() : fonction PHP native pour vérifier le type
        // - iterator_to_array() : fonction PHP native pour convertir Iterator → array
        //
        // Opérateur ternaire : condition ? siVrai : siFaux
        $sources = is_array($sources) ? $sources : iterator_to_array($sources);
        
        // ===== 3f. BOUCLE - COMPTAGE ET AFFICHAGE PAR SOURCE =====
        
        // Boucler sur chaque source pour compter ses articles
        foreach ($sources as $source) {
            // Requête MongoDB pour compter les articles de cette source spécifique
            //
            // Requête MongoDB équivalente :
            // db.articles.countDocuments({ userId: null, source: "Korben" })
            //
            // QueryBuilder : même principe que comptage total, avec filtre supplémentaire
            $count = $this->dm->createQueryBuilder(Article::class)
                ->field('userId')->equals(null)   // Filtre 1 : articles publics
                ->field('source')->equals($source) // Filtre 2 : source spécifique
                ->count()                          // COUNT au lieu de SELECT
                ->getQuery()                       // Construction de la Query
                ->execute();                       // Exécution
            
            // Affichage de la ligne avec formatage
            // • : bullet point Unicode pour liste
            // <comment>...</comment> : couleur jaune/orange dans le terminal
            $io->writeln("  • {$source} : <comment>{$count}</comment>");
        }
        
        // ===== 3g. AFFICHAGE DU MESSAGE DE SUCCÈS =====
        
        // success() : affiche un bloc vert avec icône ✔ et message
        $io->success('Comptage terminé !');
        
        // ===== 3h. RETOUR DU CODE DE SORTIE =====
        
        // Command::SUCCESS : constante = 0 (code de retour shell pour succès)
        // 
        // Codes de retour shell :
        // - 0 = succès (Command::SUCCESS)
        // - 1 = erreur générique (Command::FAILURE)
        // - 2+ = erreurs spécifiques personnalisées
        //
        // Utilisé par :
        // - Scripts bash : if php bin/console app:count-articles; then echo "OK"; fi
        // - Cron jobs : détection d'échec d'exécution
        return Command::SUCCESS;
    }
}