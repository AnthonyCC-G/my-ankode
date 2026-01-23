<?php

namespace App\Tests\Controller;

use App\Document\Article;
use App\Tests\ApiTestCase;

/**
 * Tests pour VeilleController (API Articles MongoDB)
 * Teste les fonctionnalités de marquage lu/non-lu et favoris
 */
class VeilleControllerTest extends ApiTestCase
{
    /**
     * Test 1 : PATCH /api/articles/{id}/mark-read
     * Doit marquer un article comme lu
     * Protection CSRF
     */
    public function testMarkArticleAsReadSuccess(): void
    {
        // Arrange : Créer un user et un article MongoDB
        $user = $this->createUser('veille_user@test.com');
        $userId = (string) $user->getId();
        
        $article = new Article();
        $article->setTitle('Article de test');
        $article->setUrl('https://example.com/article');
        $article->setDescription('Description de l\'article');
        $article->setPublishedAt(new \DateTimeImmutable()); // ✅ DateTimeImmutable
        $article->setSource('Test Source');
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        $articleId = $article->getId();
        
        // Clear le cache MongoDB
        $this->documentManager->clear();
        
        // Act : Marquer comme lu
        $this->loginUser($user);
        $this->apiRequest('PATCH', '/api/articles/' . $articleId . '/mark-read');
        
        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertTrue($response['isRead']);
        
        // Vérifier en BDD que l'article est bien marqué comme lu
        $updatedArticle = $this->documentManager->getRepository(Article::class)->find($articleId);
        $this->assertTrue($updatedArticle->isReadByUser($userId));
    }

    /**
     * Test 2 : PATCH /api/articles/{id}/mark-read (toggle)
     * Doit démarquer un article déjà lu
     * Protection CSRF
     */
    public function testUnmarkArticleAsReadSuccess(): void
    {
        // Arrange : Créer un article déjà marqué comme lu
        $user = $this->createUser('veille_user@test.com');
        $userId = (string) $user->getId();
        
        $article = new Article();
        $article->setTitle('Article déjà lu');
        $article->setUrl('https://example.com/article-lu');
        $article->setDescription('Article déjà marqué comme lu');
        $article->setPublishedAt(new \DateTimeImmutable()); // ✅ DateTimeImmutable
        $article->setSource('Test Source');
        $article->toggleReadByUser($userId); // Marquer comme lu
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        $articleId = $article->getId();
        $this->documentManager->clear();
        
        // Act : Toggle (démarquer comme lu)
        $this->loginUser($user);
        $this->apiRequest('PATCH', '/api/articles/' . $articleId . '/mark-read');
        
        // Assert
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertFalse($response['isRead']); // Plus lu maintenant
        
        // Vérifier en BDD
        $updatedArticle = $this->documentManager->getRepository(Article::class)->find($articleId);
        $this->assertFalse($updatedArticle->isReadByUser($userId));
    }

    /**
     * Test 3 : POST /api/articles/{id}/favorite
     * Doit ajouter un article aux favoris
     * Protection CSRF
     */
    public function testAddArticleToFavoritesSuccess(): void
    {
        // Arrange
        $user = $this->createUser('veille_user@test.com');
        $userId = (string) $user->getId();
        
        $article = new Article();
        $article->setTitle('Article à favoriser');
        $article->setUrl('https://example.com/favorite');
        $article->setDescription('Article qui sera ajouté aux favoris');
        $article->setPublishedAt(new \DateTimeImmutable()); // ✅ DateTimeImmutable
        $article->setSource('Test Source');
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        $articleId = $article->getId();
        $this->documentManager->clear();
        
        // Act
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/articles/' . $articleId . '/favorite');
        
        // Assert
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertTrue($response['isFavorite']);
        
        // Vérifier en BDD
        $updatedArticle = $this->documentManager->getRepository(Article::class)->find($articleId);
        $this->assertTrue($updatedArticle->isFavoritedByUser($userId));
    }

    /**
     * Test 4 : DELETE /api/articles/{id}/favorite
     * Doit retirer un article des favoris
     * Protection CSRF
     */
    public function testRemoveArticleFromFavoritesSuccess(): void
    {
        // Arrange : Article déjà en favoris
        $user = $this->createUser('veille_user@test.com');
        $userId = (string) $user->getId();
        
        $article = new Article();
        $article->setTitle('Article en favoris');
        $article->setUrl('https://example.com/remove-favorite');
        $article->setDescription('Article déjà en favoris');
        $article->setPublishedAt(new \DateTimeImmutable()); // ✅ DateTimeImmutable
        $article->setSource('Test Source');
        $article->addToFavorites($userId); // Ajouter aux favoris
        
        $this->documentManager->persist($article);
        $this->documentManager->flush();
        
        $articleId = $article->getId();
        $this->documentManager->clear();
        
        // Act
        $this->loginUser($user);
        $this->apiRequest('DELETE', '/api/articles/' . $articleId . '/favorite');
        
        // Assert
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertFalse($response['isFavorite']);
        
        // Vérifier en BDD
        $updatedArticle = $this->documentManager->getRepository(Article::class)->find($articleId);
        $this->assertFalse($updatedArticle->isFavoritedByUser($userId));
    }

    /**
     * Test 5 : PATCH /api/articles/{id}/mark-read
     * Doit retourner 404 si l'article n'existe pas
     */
    public function testMarkReadArticleNotFound(): void
    {
        // Arrange
        $user = $this->createUser('veille_user@test.com');
        
        // Act : Utiliser un ID inexistant (format MongoDB ObjectId)
        $this->loginUser($user);
        $this->apiRequest('PATCH', '/api/articles/507f1f77bcf86cd799439011/mark-read');
        
        // Assert
        $this->assertResponseStatusCodeSame(404);
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * Test 6 : POST /api/articles/{id}/favorite
     * Doit retourner 404 si l'article n'existe pas
     */
    public function testAddFavoriteArticleNotFound(): void
    {
        // Arrange
        $user = $this->createUser('veille_user@test.com');
        
        // Act : Utiliser un ID inexistant
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/articles/507f1f77bcf86cd799439011/favorite');
        
        // Assert
        $this->assertResponseStatusCodeSame(404);
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * Test 7 : DELETE /api/articles/{id}/favorite
     * Doit retourner 404 si l'article n'existe pas
     */
    public function testRemoveFavoriteArticleNotFound(): void
    {
        // Arrange
        $user = $this->createUser('veille_user@test.com');
        
        // Act : Utiliser un ID inexistant
        $this->loginUser($user);
        $this->apiRequest('DELETE', '/api/articles/507f1f77bcf86cd799439011/favorite');
        
        // Assert
        $this->assertResponseStatusCodeSame(404);
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('error', $response);
    }
}