<?php

namespace App\Tests\Controller;

use App\Document\Snippet;
use App\Tests\ApiTestCase;

/**
 * Tests fonctionnels pour SnippetController (API REST MongoDB)
 * Teste les routes CRUD + protection CSRF + ownership MongoDB
 */
class SnippetControllerTest extends ApiTestCase
{
    /**
     * TEST 1 : GET /api/snippets
     * Doit retourner la liste des snippets de l'utilisateur connecté (MongoDB)
     */
    public function testGetSnippetsSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        $snippet1 = new Snippet();
        $snippet1->setTitle('Connexion MySQL');
        $snippet1->setLanguage('php');
        $snippet1->setCode('<?php $pdo = new PDO(...);');
        $snippet1->setDescription('Connexion à MySQL avec PDO');
        $snippet1->setUserId((string) $user->getId());
        $this->documentManager->persist($snippet1);
        
        $snippet2 = new Snippet();
        $snippet2->setTitle('Boucle foreach');
        $snippet2->setLanguage('php');
        $snippet2->setCode('foreach ($array as $item) {}');
        $snippet2->setUserId((string) $user->getId());
        $this->documentManager->persist($snippet2);
        
        $this->documentManager->flush();
        
        // ACT
        $this->loginUser($user);
        $this->client->request('GET', '/api/snippets');
        
        // ASSERT
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        
        $response = $this->getJsonResponse();
        $this->assertCount(2, $response);
        
        // Vérifier que les snippets sont présents (ordre non garanti)
        $titles = array_column($response, 'title');
        $this->assertContains('Connexion MySQL', $titles);
        $this->assertContains('Boucle foreach', $titles);
    }

    /**
     * TEST 2 : GET /api/snippets/{id}
     * Doit retourner un snippet spécifique (MongoDB)
     */
    public function testGetSnippetSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        $snippet = new Snippet();
        $snippet->setTitle('Array map en JavaScript');
        $snippet->setLanguage('javascript');
        $snippet->setCode('const doubled = arr.map(x => x * 2);');
        $snippet->setDescription('Transformer un tableau');
        $snippet->setUserId((string) $user->getId());
        $this->documentManager->persist($snippet);
        $this->documentManager->flush();
        
        // ACT
        $this->loginUser($user);
        $this->client->request('GET', '/api/snippets/' . $snippet->getId());
        
        // ASSERT
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertEquals('Array map en JavaScript', $response['title']);
        $this->assertEquals('javascript', $response['language']);
        $this->assertStringContainsString('map', $response['code']);
    }

    /**
     * TEST 3 : POST /api/snippets
     * Doit créer un nouveau snippet dans MongoDB avec CSRF
     */
    public function testCreateSnippetSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/snippets', [
            'title' => 'Promise async/await',
            'language' => 'javascript',
            'code' => 'async function fetchData() { const data = await fetch(url); }',
            'description' => 'Utilisation des promesses'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        $this->assertEquals('Promise async/await', $response['title']);
        $this->assertEquals('javascript', $response['language']);
    }

    /**
     * TEST 4 : POST /api/snippets sans title
     * Doit retourner 400 Bad Request
     */
    public function testCreateSnippetWithoutTitleFails(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/snippets', [
            'language' => 'python',
            'code' => 'print("Hello")'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(400);
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * TEST 5 : POST /api/snippets sans language
     * Doit retourner 400 Bad Request
     */
    public function testCreateSnippetWithoutLanguageFails(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/snippets', [
            'title' => 'Mon snippet',
            'code' => 'print("Hello")'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(400);
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * TEST 6 : POST /api/snippets sans code
     * Doit retourner 400 Bad Request
     */
    public function testCreateSnippetWithoutCodeFails(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/snippets', [
            'title' => 'Mon snippet',
            'language' => 'python'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(400);
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * TEST 7 : PUT /api/snippets/{id}
     * Doit modifier un snippet MongoDB
     */
    public function testUpdateSnippetSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        $snippet = new Snippet();
        $snippet->setTitle('Ancien titre');
        $snippet->setLanguage('python');
        $snippet->setCode('print("old")');
        $snippet->setDescription('Ancienne description');
        $snippet->setUserId((string) $user->getId());
        $this->documentManager->persist($snippet);
        $this->documentManager->flush();
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('PUT', '/api/snippets/' . $snippet->getId(), [
            'title' => 'Nouveau titre',
            'language' => 'python',
            'code' => 'print("new")',
            'description' => 'Nouvelle description'
        ]);
        
        // ASSERT
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertEquals('Nouveau titre', $response['title']);
        $this->assertEquals('print("new")', $response['code']);
    }

    /**
     * TEST 8 : DELETE /api/snippets/{id}
     * Doit supprimer un snippet MongoDB
     */
    public function testDeleteSnippetSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        $snippet = new Snippet();
        $snippet->setTitle('Snippet à supprimer');
        $snippet->setLanguage('javascript');
        $snippet->setCode('console.log("delete me");');
        $snippet->setUserId((string) $user->getId());
        $this->documentManager->persist($snippet);
        $this->documentManager->flush();
        
        $snippetId = $snippet->getId();
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('DELETE', '/api/snippets/' . $snippetId);
        
        // ASSERT
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('message', $response);
        
        // Vérifie que le snippet n'existe plus en MongoDB
        $deletedSnippet = $this->documentManager->getRepository(Snippet::class)->find($snippetId);
        $this->assertNull($deletedSnippet);
    }

    /**
     * TEST 9 : GET /api/snippets/{id} d'un autre user
     * Doit retourner 403 Forbidden (ownership protection MongoDB)
     */
    public function testGetSnippetForbiddenForOtherUser(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $snippet = new Snippet();
        $snippet->setTitle('Snippet du owner');
        $snippet->setLanguage('php');
        $snippet->setCode('<?php echo "private";');
        $snippet->setUserId((string) $owner->getId());
        $this->documentManager->persist($snippet);
        $this->documentManager->flush();
        
        // ACT : Connexion avec un autre user
        $this->loginUser($otherUser);
        $this->client->request('GET', '/api/snippets/' . $snippet->getId());
        
        // ASSERT : Doit être refusé
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 10 : PUT /api/snippets/{id} d'un autre user
     * Doit retourner 403 Forbidden
     */
    public function testUpdateSnippetForbiddenForOtherUser(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $snippet = new Snippet();
        $snippet->setTitle('Snippet du owner');
        $snippet->setLanguage('python');
        $snippet->setCode('print("private")');
        $snippet->setUserId((string) $owner->getId());
        $this->documentManager->persist($snippet);
        $this->documentManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->apiRequest('PUT', '/api/snippets/' . $snippet->getId(), [
            'title' => 'Tentative de piratage',
            'code' => 'hacked'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 11 : DELETE /api/snippets/{id} d'un autre user
     * Doit retourner 403 Forbidden
     */
    public function testDeleteSnippetForbiddenForOtherUser(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $snippet = new Snippet();
        $snippet->setTitle('Snippet du owner');
        $snippet->setLanguage('javascript');
        $snippet->setCode('console.log("private");');
        $snippet->setUserId((string) $owner->getId());
        $this->documentManager->persist($snippet);
        $this->documentManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->apiRequest('DELETE', '/api/snippets/' . $snippet->getId());
        
        // ASSERT
        $this->assertResponseStatusCodeSame(403);
    }
}