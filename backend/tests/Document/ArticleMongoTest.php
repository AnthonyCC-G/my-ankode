<?php

namespace App\Tests\Document;

use App\Document\Article;
use App\Tests\ApiTestCase;

/**
 * Tests MongoDB pour le Document Article (Veille technologique)
 * Vérifie que la fonctionnalité signature de MY-ANKODE fonctionne
 */
class ArticleMongoTest extends ApiTestCase
{
    /**
     * Test 1 : Créer un Article dans MongoDB
     * Vérifie que l'insertion fonctionne correctement
     */
    public function testCreateArticleSuccess(): void
    {
        // Arrange
        $user = $this->createUser('mongo_user@test.com');
        
        $article = new Article();
        $article->setTitle('Test Article MongoDB');
        $article->setUrl('https://example.com/test-article');
        $article->setDescription('Article de test pour MongoDB');
        $article->setSource('Test Source');
        $article->setUserId((string) $user->getId());
        $article->setPublishedAt(new \DateTimeImmutable());
        $article->setTags(['test', 'mongodb', 'php']);
        
        // Act
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        // Assert
        $this->assertNotNull($article->getId());
        $this->assertEquals('Test Article MongoDB', $article->getTitle());
        $this->assertEquals('https://example.com/test-article', $article->getUrl());
        $this->assertFalse($article->isRead());
        $this->assertEquals(['test', 'mongodb', 'php'], $article->getTags());
    }

    /**
     * Test 2 : Récupérer un Article par son ID
     * Vérifie la lecture depuis MongoDB
     */
    public function testFindArticleById(): void
    {
        // Arrange
        $user = $this->createUser('mongo_reader@test.com');
        
        $article = new Article();
        $article->setTitle('Article à retrouver');
        $article->setUrl('https://example.com/find-me');
        $article->setUserId((string) $user->getId());
        $article->setSource('Dev.to');
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        $articleId = $article->getId();
        
        // Clear pour simuler une nouvelle requête
        $this->documentManager->clear();
        
        // Act
        $repository = $this->documentManager->getRepository(Article::class);
        $foundArticle = $repository->find($articleId);
        
        // Assert
        $this->assertNotNull($foundArticle);
        $this->assertEquals('Article à retrouver', $foundArticle->getTitle());
        $this->assertEquals('https://example.com/find-me', $foundArticle->getUrl());
        $this->assertEquals('Dev.to', $foundArticle->getSource());
    }

    /**
     * Test 3 : Récupérer les articles d'un utilisateur
     * Vérifie le filtering par userId (fonctionnalité clé)
     */
    public function testFindArticlesByUser(): void
    {
        // Arrange
        $user1 = $this->createUser('user1_articles@test.com');
        $user2 = $this->createUser('user2_articles@test.com');
        
        // Articles de user1
        $article1 = new Article();
        $article1->setTitle('Article User 1 - 1');
        $article1->setUrl('https://example.com/user1-1');
        $article1->setUserId((string) $user1->getId());
        $this->documentManager->persist($article1);
        
        $article2 = new Article();
        $article2->setTitle('Article User 1 - 2');
        $article2->setUrl('https://example.com/user1-2');
        $article2->setUserId((string) $user1->getId());
        $this->documentManager->persist($article2);
        
        // Article de user2
        $article3 = new Article();
        $article3->setTitle('Article User 2');
        $article3->setUrl('https://example.com/user2-1');
        $article3->setUserId((string) $user2->getId());
        $this->documentManager->persist($article3);
        
        $this->documentManager->flush();
        
        // Act
        $repository = $this->documentManager->getRepository(Article::class);
        $user1Articles = $repository->findBy(['userId' => (string) $user1->getId()]);
        
        // Assert
        $this->assertCount(2, $user1Articles);
        
        // Vérifier que tous les articles appartiennent bien à user1
        foreach ($user1Articles as $article) {
            $this->assertEquals((string) $user1->getId(), $article->getUserId());
        }
    }

    /**
     * Test 4 : Marquer un article comme lu
     * Vérifie la fonctionnalité de marquage "lu/non-lu"
     */
    public function testMarkArticleAsRead(): void
    {
        // Arrange
        $user = $this->createUser('reader@test.com');
        
        $article = new Article();
        $article->setTitle('Article à lire');
        $article->setUrl('https://example.com/to-read');
        $article->setUserId((string) $user->getId());
        $article->setIsRead(false);
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        $articleId = $article->getId();
        
        // Act : Marquer comme lu
        $article->setIsRead(true);
        $this->documentManager->flush();
        
        // Clear et recharger
        $this->documentManager->clear();
        $repository = $this->documentManager->getRepository(Article::class);
        $reloadedArticle = $repository->find($articleId);
        
        // Assert
        $this->assertTrue($reloadedArticle->isRead());
    }

    /**
     * Nettoyage après chaque test
     * Supprime les articles de test pour éviter pollution
     */
    protected function tearDown(): void
    {
        // Nettoyer les articles de test créés
        $repository = $this->documentManager->getRepository(Article::class);
        $testArticles = $repository->findAll();
        
        foreach ($testArticles as $article) {
            $this->documentManager->remove($article);
        }
        
        $this->documentManager->flush();
        
        parent::tearDown();
    }
}