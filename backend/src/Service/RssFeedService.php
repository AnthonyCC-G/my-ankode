<?php

/**
 * RSSFEEDSERVICE.PHP - Service de récupération et stockage des flux RSS
 * 
 * Responsabilités :
 * - Télécharger les flux RSS depuis Internet (Korben.info, Dev.to)
 * - Parser le contenu XML des flux RSS
 * - Extraire les articles (titre, URL, description, date)
 * - Sauvegarder les articles dans MongoDB en évitant les doublons
 * - Logger toutes les opérations pour debug et monitoring
 * 
 * Architecture :
 * - Service Symfony injecté via constructeur
 * - Utilise HttpClient Symfony pour téléchargement HTTP
 * - Utilise DocumentManager Doctrine ODM pour MongoDB
 * - Utilise Logger Symfony pour traçabilité
 * - Articles publics : userId = null (partagés entre tous les utilisateurs)
 * 
 * Workflow global :
 * 1. fetchFeed() : orchestrateur principal
 * 2. parseXml() : transformation XML → tableau PHP
 * 3. storeArticles() : sauvegarde dans MongoDB avec déduplication
 * 
 * Appelé par :
 * - Command FetchRssCommand (exécution manuelle ou cron)
 * - Potentiellement un endpoint API admin (futur)
 */

namespace App\Service;

use App\Document\Article;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RssFeedService
{
    // ===== 1. INJECTION DE DÉPENDANCES - SERVICES SYMFONY =====
    
    public function __construct(
        private HttpClientInterface $httpClient,    // Pour télécharger les flux RSS via HTTP
        private DocumentManager $dm,                // Pour sauvegarder dans MongoDB
        private LoggerInterface $logger             // Pour logger les opérations (debug + production)
    ) {}

    // ===== 2. MÉTHODE PRINCIPALE - RÉCUPÉRATION D'UN FLUX RSS COMPLET =====
    
    /**
     * Récupère et parse un flux RSS
     * 
     * @param string $url URL du flux RSS
     * @param User|null $user Utilisateur propriétaire (null = articles publics)
     * @param string $sourceName Nom de la source (ex: "Dev.to")
     * @return array{success: bool, count: int, error?: string}
     */
    public function fetchFeed(string $url, ?User $user, string $sourceName): array
    {
        try {
            // ===== ÉTAPE 1 : TÉLÉCHARGEMENT DU FLUX RSS VIA HTTP =====
            
            // Log de début d'opération (visible dans var/log/dev.log ou prod.log)
            $this->logger->info("Fetching RSS feed", ['url' => $url]);
            
            // Requête HTTP GET vers l'URL du flux RSS
            // HttpClient Symfony : similaire à fetch() en JavaScript ou curl en PHP
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,  // Timeout de 10 secondes (évite blocage si serveur lent)
                'headers' => [
                    // User-Agent : identification de notre application (politesse HTTP)
                    'User-Agent' => 'MY-ANKODE/1.0 RSS Reader'
                ]
            ]);

            // Vérification du code HTTP (200 = succès)
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Erreur HTTP: " . $response->getStatusCode());
            }

            // Récupération du contenu de la réponse (XML brut)
            $xmlContent = $response->getContent();

            // ===== ÉTAPE 2 : PARSING DU XML EN STRUCTURE PHP =====
            
            // Transformation du XML (string) en tableau PHP exploitable
            $feed = $this->parseXml($xmlContent);

            // ===== ÉTAPE 3 : SAUVEGARDE DES ARTICLES DANS MONGODB =====
            
            // Insertion des articles dans MongoDB (avec déduplication par URL)
            $count = $this->storeArticles($feed, $user, $sourceName, $url);

            // Log de succès avec nombre d'articles importés
            $this->logger->info("RSS feed fetched successfully", [
                'url' => $url,
                'articles_count' => $count
            ]);

            // Retour de succès avec compteur d'articles importés
            return [
                'success' => true,
                'count' => $count
            ];

        } catch (\Exception $e) {
            // ===== GESTION DES ERREURS (RÉSEAU, XML, MONGODB) =====
            
            // Log d'erreur avec détails pour debug
            $this->logger->error("Error fetching RSS feed", [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            // Retour d'échec avec message d'erreur
            return [
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    // ===== 3. PARSING XML - TRANSFORMATION XML → TABLEAU PHP =====
    
    /**
     * Parse le contenu XML du flux RSS
     * 
     * Explications XML/RSS pour débutants :
     * - Un flux RSS est un fichier XML avec structure standardisée
     * - Structure type : <rss><channel><item><title>...</title><link>...</link></item></channel></rss>
     * - On extrait chaque <item> qui représente un article
     * 
     * @param string $xmlContent Contenu XML du flux
     * @return array Array d'items avec title, link, pubDate, description
     */
    private function parseXml(string $xmlContent): array
    {
        // ===== 3a. CONFIGURATION DU PARSER XML PHP =====
        
        // Désactive les erreurs XML natives PHP (on les gérera manuellement)
        // Par défaut PHP affiche des warnings, ici on veut les capturer proprement
        libxml_use_internal_errors(true);

        // Parsing du XML en objet SimpleXML (structure arborescente PHP)
        // simplexml_load_string() : fonction PHP native pour parser XML
        $xml = simplexml_load_string($xmlContent);

        // ===== 3b. VÉRIFICATION DES ERREURS DE PARSING =====
        
        if ($xml === false) {
            // Récupération des erreurs XML capturées
            $errors = libxml_get_errors();
            libxml_clear_errors(); // Nettoyage de la pile d'erreurs
            
            // Lancer une exception avec le premier message d'erreur
            throw new \Exception("Erreur de parsing XML: " . $errors[0]->message ?? 'Format invalide');
        }

        // ===== 3c. EXTRACTION DES ARTICLES DEPUIS LE XML =====
        
        $items = []; // Tableau qui contiendra tous les articles extraits

        // Parcourir chaque <item> du flux RSS
        // $xml->channel->item : accès aux éléments XML comme des propriétés d'objet
        // foreach : boucle sur tous les <item> trouvés dans <channel>
        foreach ($xml->channel->item as $item) {
            // Extraction des données de chaque article et conversion en string
            // (string) : cast nécessaire car SimpleXML retourne des objets, pas des strings
            $items[] = [
                'title' => (string) $item->title,              // Titre de l'article
                'link' => (string) $item->link,                // URL de l'article
                'pubDate' => (string) $item->pubDate,          // Date de publication
                'description' => (string) ($item->description ?? '') // Description (vide si absente)
            ];
        }

        // Retour du tableau d'articles parsés
        return $items;
    }

    // ===== 4. SAUVEGARDE MONGODB - INSERTION AVEC DÉDUPLICATION =====
    
    /**
     * Sauvegarde les articles dans MongoDB
     * 
     * Logique de déduplication :
     * - On vérifie si l'article existe déjà via son URL (unique)
     * - Si existe déjà : skip (pas de doublon)
     * - Si nouveau : création et persist
     * - Flush final : sauvegarde groupée (optimisation performance)
     * 
     * @param array $items Articles parsés depuis le flux RSS
     * @param User|null $user Utilisateur propriétaire (null = articles publics)
     * @param string $sourceName Nom de la source
     * @param string $feedUrl URL du flux
     * @return int Nombre d'articles sauvegardés
     */
    private function storeArticles(array $items, ?User $user, string $sourceName, string $feedUrl): int
    {
        $count = 0; // Compteur d'articles réellement insérés

        // Boucle sur chaque article parsé depuis le XML
        foreach ($items as $item) {
            // ===== 4a. DÉDUPLICATION - VÉRIFICATION SI ARTICLE EXISTE DÉJÀ =====
            
            // Recherche dans MongoDB par URL (champ unique de fait)
            // findOneBy() : méthode Doctrine pour rechercher UN document
            $existingArticle = $this->dm->getRepository(Article::class)
                ->findOneBy(['url' => $item['link']]);

            if ($existingArticle) {
                // Article déjà présent en base : on skip
                $this->logger->debug("Article already exists", ['url' => $item['link']]);
                continue; // Passe à l'article suivant dans la boucle
            }

            // ===== 4b. CRÉATION DU DOCUMENT MONGODB ARTICLE =====
            
            // Créer le nouvel article (Document MongoDB)
            $article = new Article();
            $article->setTitle($item['title']);
            $article->setUrl($item['link']);
            
            // ===== 4c. NETTOYAGE DE LA DESCRIPTION (SÉCURITÉ + QUALITÉ) =====
            
            // POURQUOI NETTOYER ? 
            // - Les flux RSS contiennent souvent du HTML dans la description
            // - Risque XSS si on affiche ce HTML côté client
            // - Descriptions parfois très longues (plusieurs Ko)
            
            // strip_tags() : fonction PHP native qui enlève toutes les balises HTML
            // Exemple : "<p>Hello <b>world</b></p>" devient "Hello world"
            $cleanDescription = strip_tags($item['description']); // Enlève HTML
            
            // preg_replace('/\s+/', ' ', ...) : remplace espaces multiples par un seul espace
            // \s+ : regex pour "un ou plusieurs espaces/tabs/newlines"
            // Exemple : "Hello    world\n\ntest" devient "Hello world test"
            $cleanDescription = preg_replace('/\s+/', ' ', $cleanDescription); // Espaces multiples → 1
            
            // trim() : enlève les espaces au début et fin de string
            $cleanDescription = trim($cleanDescription); // Trim
            
            // mb_substr() : découpe la string à 250 caractères (multibyte safe = UTF-8 compatible)
            // Pourquoi 250 ? Limite raisonnable pour preview dans l'interface
            $cleanDescription = mb_substr($cleanDescription, 0, 250); // Max 250 caractères

            $article->setDescription($cleanDescription);
            $article->setSource($sourceName);
            $article->setUserId(null);
            $article->setSource($sourceName);
            
            // userId = null pour articles publics RSS
            // Tous les utilisateurs voient les mêmes articles RSS
            $article->setUserId(null);

            // ===== 4d. PARSING DE LA DATE DE PUBLICATION =====
            
            // Parser la date de publication (format RSS : "Mon, 01 Jan 2024 12:00:00 GMT")
            if (!empty($item['pubDate'])) {
                try {
                    // DateTimeImmutable : objet PHP pour gérer les dates (immutable = non modifiable)
                    // Le constructeur accepte plein de formats de dates (RFC 2822, ISO 8601, etc.)
                    $publishedAt = new \DateTimeImmutable($item['pubDate']);
                    $article->setPublishedAt($publishedAt);
                } catch (\Exception $e) {
                    // Si le format de date est invalide, on log un warning mais on continue
                    $this->logger->warning("Invalid date format", [
                        'pubDate' => $item['pubDate'],
                        'error' => $e->getMessage()
                    ]);
                    // Article sera créé sans date (publishedAt = null)
                }
            }

            // ===== 4e. PERSISTANCE MONGODB (MARQUAGE POUR INSERTION) =====
            
            // persist() : marque le document pour insertion (pas encore en base)
            // Comme un "git add" : on prépare l'insertion mais elle n'est pas encore réelle
            $this->dm->persist($article);
            $count++; // Incrémenter le compteur d'articles à insérer
        }

        // ===== 4f. FLUSH FINAL - SAUVEGARDE GROUPÉE EN BASE =====
        
        // Sauvegarder tous les articles en une seule fois (optimisation performance)
        if ($count > 0) {
            // flush() : exécution réelle de toutes les insertions marquées par persist()
            // Comme un "git commit" : on envoie vraiment les données en base
            // UNE SEULE requête MongoDB au lieu de N requêtes (N = nombre d'articles)
            $this->dm->flush();
        }

        // Retour du nombre d'articles réellement insérés
        return $count;
    }
}