<?php

namespace App\Service;

use App\Document\Article;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RssFeedService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private DocumentManager $dm,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère et parse un flux RSS
     * 
     * @param string $url URL du flux RSS
     * @param User $user Utilisateur propriétaire des articles
     * @param string $sourceName Nom de la source (ex: "Dev.to")
     * @return array{success: bool, count: int, error?: string}
     */
    public function fetchFeed(string $url, User $user, string $sourceName): array
    {
        try {
            // Étape 1 : Télécharger le flux RSS
            $this->logger->info("Fetching RSS feed", ['url' => $url]);
            
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'MY-ANKODE/1.0 RSS Reader'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Erreur HTTP: " . $response->getStatusCode());
            }

            $xmlContent = $response->getContent();

            // Étape 2 : Parser le XML
            $feed = $this->parseXml($xmlContent);

            // Étape 3 : Sauvegarder les articles
            $count = $this->storeArticles($feed, $user, $sourceName, $url);

            $this->logger->info("RSS feed fetched successfully", [
                'url' => $url,
                'articles_count' => $count
            ]);

            return [
                'success' => true,
                'count' => $count
            ];

        } catch (\Exception $e) {
            $this->logger->error("Error fetching RSS feed", [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse le contenu XML du flux RSS
     * 
     * @param string $xmlContent Contenu XML du flux
     * @return array Array d'items avec title, link, pubDate, description
     */
    private function parseXml(string $xmlContent): array
    {
        // Désactive les erreurs XML pour les gérer manuellement
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \Exception("Erreur de parsing XML: " . $errors[0]->message ?? 'Format invalide');
        }

        $items = [];

        // Parcourir chaque <item> du flux
        foreach ($xml->channel->item as $item) {
            $items[] = [
                'title' => (string) $item->title,
                'link' => (string) $item->link,
                'pubDate' => (string) $item->pubDate,
                'description' => (string) ($item->description ?? '')
            ];
        }

        return $items;
    }

    /**
     * Sauvegarde les articles dans MongoDB
     * 
     * @param array $items Articles parsés depuis le flux RSS
     * @param User $user Utilisateur propriétaire
     * @param string $sourceName Nom de la source
     * @param string $feedUrl URL du flux
     * @return int Nombre d'articles sauvegardés
     */
    private function storeArticles(array $items, User $user, string $sourceName, string $feedUrl): int
    {
        $count = 0;

        foreach ($items as $item) {
            // Vérifier si l'article existe déjà (éviter les doublons)
            $existingArticle = $this->dm->getRepository(Article::class)
                ->findOneBy(['url' => $item['link']]);

            if ($existingArticle) {
                $this->logger->debug("Article already exists", ['url' => $item['link']]);
                continue;
            }

            // Créer le nouvel article
            $article = new Article();
            $article->setTitle($item['title']);
            $article->setUrl($item['link']);
            $article->setDescription($item['description']);
            $article->setSource($sourceName);
            $article->setUserId((string) $user->getId());

            // Parser la date de publication
            if (!empty($item['pubDate'])) {
                try {
                    $publishedAt = new \DateTimeImmutable($item['pubDate']);
                    $article->setPublishedAt($publishedAt);
                } catch (\Exception $e) {
                    $this->logger->warning("Invalid date format", [
                        'pubDate' => $item['pubDate'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->dm->persist($article);
            $count++;
        }

        // Sauvegarder tous les articles en une seule fois
        if ($count > 0) {
            $this->dm->flush();
        }

        return $count;
    }
}