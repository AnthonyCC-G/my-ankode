<?php

namespace App\Tests\Document;

use App\Document\Article;
use App\Tests\ApiTestCase;

/**
 * Tests MongoDB pour le Document Article (Veille technologique)
 * Vérifie que la fonctionnalité signature de MY-ANKODE fonctionne
 * 
 * MISE À JOUR : Support du système multi-utilisateurs (readBy / favoritedBy)
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
        
        // Utilise le système multi-utilisateurs
        $this->assertFalse($article->isReadByUser((string) $user->getId()));
        $this->assertFalse($article->isFavoritedByUser((string) $user->getId()));
        
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
     * Test 4 : Marquer un article comme lu (système multi-utilisateurs)
     * Vérifie que chaque utilisateur a son propre état de lecture
     */
    public function testMarkArticleAsReadMultiUser(): void
    {
        // Arrange : Créer 2 utilisateurs et 1 article
        $user1 = $this->createUser('reader1@test.com');
        $user2 = $this->createUser('reader2@test.com');
        $userId1 = (string) $user1->getId();
        $userId2 = (string) $user2->getId();
        
        $article = new Article();
        $article->setTitle('Article partagé');
        $article->setUrl('https://example.com/shared-article');
        $article->setPublishedAt(new \DateTimeImmutable());
        // Article public (userId = null, visible par tous)
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        $articleId = $article->getId();
        
        // Act : User1 marque l'article comme lu
        $article->markAsReadByUser($userId1);
        $this->documentManager->flush();
        
        // Clear et recharger
        $this->documentManager->clear();
        $repository = $this->documentManager->getRepository(Article::class);
        $reloadedArticle = $repository->find($articleId);
        
        // Assert : User1 a lu, User2 n'a pas lu
        $this->assertTrue($reloadedArticle->isReadByUser($userId1));
        $this->assertFalse($reloadedArticle->isReadByUser($userId2));
        
        // Act : User2 marque aussi l'article comme lu
        $reloadedArticle->markAsReadByUser($userId2);
        $this->documentManager->flush();
        $this->documentManager->clear();
        
        $finalArticle = $repository->find($articleId);
        
        // Assert : Les deux ont lu l'article maintenant
        $this->assertTrue($finalArticle->isReadByUser($userId1));
        $this->assertTrue($finalArticle->isReadByUser($userId2));
        $this->assertEquals(2, $finalArticle->getReadCount());
    }

    /**
     * Test 5 : Toggle l'état de lecture d'un article
     * Vérifie que toggleReadByUser() fonctionne correctement
     */
    public function testToggleReadStatus(): void
    {
        // Arrange
        $user = $this->createUser('toggler@test.com');
        $userId = (string) $user->getId();
        
        $article = new Article();
        $article->setTitle('Article à toggler');
        $article->setUrl('https://example.com/toggle');
        $article->setPublishedAt(new \DateTimeImmutable());
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        // Assert : Initialement non lu
        $this->assertFalse($article->isReadByUser($userId));
        
        // Act : Premier toggle → lu
        $article->toggleReadByUser($userId);
        $this->documentManager->flush();
        
        // Assert : Maintenant lu
        $this->assertTrue($article->isReadByUser($userId));
        
        // Act : Deuxième toggle → non lu
        $article->toggleReadByUser($userId);
        $this->documentManager->flush();
        
        // Assert : De nouveau non lu
        $this->assertFalse($article->isReadByUser($userId));
    }

    /**
     * Test 6 : Ajouter un article aux favoris
     * Vérifie la fonctionnalité de favoris multi-utilisateurs
     */
    public function testAddArticleToFavorites(): void
    {
        // Arrange
        $user = $this->createUser('favorites@test.com');
        $userId = (string) $user->getId();
        
        $article = new Article();
        $article->setTitle('Article à favoriser');
        $article->setUrl('https://example.com/favorite-me');
        $article->setPublishedAt(new \DateTimeImmutable());
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        // Assert : Initialement pas en favoris
        $this->assertFalse($article->isFavoritedByUser($userId));
        
        // Act : Ajouter aux favoris
        $article->addToFavorites($userId);
        $this->documentManager->flush();
        
        // Assert : Maintenant en favoris
        $this->assertTrue($article->isFavoritedByUser($userId));
        $this->assertEquals(1, $article->getFavoritesCount());
    }

    /**
     * Test 7 : Retirer un article des favoris
     * Vérifie la suppression des favoris
     */
    public function testRemoveArticleFromFavorites(): void
    {
        // Arrange : Article déjà en favoris
        $user = $this->createUser('unfavorite@test.com');
        $userId = (string) $user->getId();
        
        $article = new Article();
        $article->setTitle('Article en favoris');
        $article->setUrl('https://example.com/remove-favorite');
        $article->setPublishedAt(new \DateTimeImmutable());
        $article->addToFavorites($userId); // Déjà en favoris
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        // Assert : Initialement en favoris
        $this->assertTrue($article->isFavoritedByUser($userId));
        
        // Act : Retirer des favoris
        $article->removeFromFavorites($userId);
        $this->documentManager->flush();
        
        // Assert : Plus en favoris
        $this->assertFalse($article->isFavoritedByUser($userId));
        $this->assertEquals(0, $article->getFavoritesCount());
    }

    /**
     * Test 8 : Toggle favori (comme le bouton dans l'UI)
     * Vérifie que toggleFavorite() fonctionne
     */
    public function testToggleFavoriteStatus(): void
    {
        // Arrange
        $user = $this->createUser('toggle_fav@test.com');
        $userId = (string) $user->getId();
        
        $article = new Article();
        $article->setTitle('Article toggle favori');
        $article->setUrl('https://example.com/toggle-fav');
        $article->setPublishedAt(new \DateTimeImmutable());
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        // Assert : Initialement pas en favoris
        $this->assertFalse($article->isFavoritedByUser($userId));
        
        // Act : Premier toggle → en favoris
        $article->toggleFavorite($userId);
        $this->documentManager->flush();
        
        // Assert : Maintenant en favoris
        $this->assertTrue($article->isFavoritedByUser($userId));
        
        // Act : Deuxième toggle → plus en favoris
        $article->toggleFavorite($userId);
        $this->documentManager->flush();
        
        // Assert : De nouveau non favori
        $this->assertFalse($article->isFavoritedByUser($userId));
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