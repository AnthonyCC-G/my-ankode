<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Classe de base pour tous les tests API REST
 * Fournit des helpers pour authentification et requêtes JSON
 * Supporte PostgreSQL (EntityManager) et MongoDB (DocumentManager)
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected DocumentManager $documentManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Création du client HTTP de test
        $this->client = static::createClient();
        
        // Récupération de l'EntityManager pour créer des fixtures à la volée (PostgreSQL)
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();
        
        // Récupération du DocumentManager pour MongoDB
        $this->documentManager = static::getContainer()
            ->get('doctrine_mongodb.odm.document_manager');
    }

    /**
     * Helper pour connecter un utilisateur en test
     * Simule une session authentifiée (équivalent à se connecter sur le site)
     */
    protected function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }

    /**
     * Helper pour créer un User de test rapidement
     * Utile pour éviter de répéter le code de création dans chaque test
     */
    protected function createUser(string $email = 'test@example.com', string $password = 'password123'): User
    {
        // Rend l'email unique avec timestamp
        $uniqueEmail = str_replace('@', '_' . uniqid() . '@', $email);
        
        $user = new User();
        $user->setEmail($uniqueEmail);
        
        // Génère username depuis l'email unique
        $username = explode('@', $uniqueEmail)[0];
        $user->setUsername($username);
        
        $user->setPassword(
            static::getContainer()->get('security.user_password_hasher')->hashPassword($user, $password)
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Helper pour faire une requête JSON (POST, PUT, PATCH)
     * Simplifie l'envoi de données JSON (comme dans Postman)
     */
    protected function jsonRequest(string $method, string $uri, array $data = []): void
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
    }

    /**
     * Helper pour récupérer la réponse JSON décodée
     * Retourne un array PHP exploitable
     */
    protected function getJsonResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Helper pour vérifier le code HTTP de la réponse
     */
    protected function assertResponseStatusCode(int $expectedCode): void
    {
        $this->assertResponseStatusCodeSame($expectedCode);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Fermeture EntityManager pour éviter fuites mémoire entre tests
        $this->entityManager->close();
        
        // Nettoyage MongoDB : Fermeture DocumentManager
        $this->documentManager->clear();
    }
}