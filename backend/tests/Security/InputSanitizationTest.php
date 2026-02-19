<?php

namespace App\Tests\Security;

use App\Document\Snippet;
use App\Entity\Project;
use App\Entity\Task;
use App\Tests\ApiTestCase;

/**
 * Tests de sanitization des entrées utilisateur
 * 
 * Vérifie la protection contre :
 * - Injection SQL (Doctrine PDO prepared statements)
 * - XSS (Cross-Site Scripting)
 * - HTML injection
 * - NoSQL injection (MongoDB)
 * 
 */
class InputSanitizationTest extends ApiTestCase
{
    /**
     * TEST 1 : Injection SQL bloquée par Doctrine (prepared statements)
     * Route utilisée : POST /api/projects
     */
    public function testSqlInjectionIsBlocked(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com');
        
        // ACT : Tentative d'injection SQL dans le nom du projet
        $maliciousName = "Mon Projet'; DROP TABLE project; --";
        
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/projects', [
            'name' => $maliciousName,
            'description' => 'Test injection SQL'
        ]);
        
        // ASSERT : Le projet est créé SANS exécuter la requête SQL malveillante
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        
        // Le nom est stocké TEL QUEL (échappé par Doctrine)
        $this->assertEquals($maliciousName, $response['project']['name']);
        
        // Vérifier que la table project existe toujours
        $projects = $this->entityManager->getRepository(Project::class)->findAll();
        $this->assertNotEmpty($projects, 'La table project doit toujours exister (pas DROP TABLE)');
    }

    /**
     * TEST 2 : Script JavaScript injecté est stocké sans exécution (protection XSS)
     * Route utilisée : POST /api/projects
     */
    public function testXssScriptIsStoredSafely(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com');
        
        // ACT : Tentative d'injection XSS
        $xssPayload = '<script>alert("XSS")</script>';
        
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/projects', [
            'name' => 'Projet XSS',
            'description' => $xssPayload
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        
        // Le script est stocké tel quel en BDD (pas d'exécution côté serveur)
        $this->assertEquals($xssPayload, $response['project']['description']);
        
        // Note : Twig échappera automatiquement lors du rendu HTML
        // L'API stocke sans exécuter = comportement correct
    }

    /**
     * TEST 3 : Balises HTML dans les champs texte sont stockées sans exécution
     * Route utilisée : POST /api/projects/{id}/tasks
     */
    public function testHtmlTagsAreStoredSafely(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT : Injecter des balises HTML dans le titre d'une tâche
        $htmlPayload = '<h1>Titre</h1><b>Bold</b><img src=x onerror=alert(1)>';
        
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/projects/' . $project->getId() . '/tasks', [
            'title' => $htmlPayload,
            'status' => 'todo',
            'position' => 1
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        
        // Le HTML est stocké tel quel (sera échappé par Twig lors du rendu)
        $this->assertEquals($htmlPayload, $response['task']['title']);
    }

    /**
     * TEST 4 : Caractères spéciaux SQL sont échappés correctement
     * Route utilisée : POST /api/projects
     */
    public function testSpecialCharactersAreHandledSafely(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com');
        
        // ACT : Caractères spéciaux qui pourraient casser les requêtes SQL
        $specialChars = "Test avec ' \" \\ % _ ; -- /* */";
        
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/projects', [
            'name' => $specialChars,
            'description' => 'Test caractères spéciaux'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        
        // Les caractères spéciaux sont stockés correctement
        $this->assertEquals($specialChars, $response['project']['name']);
        
        // Vérifier qu'on peut récupérer le projet
        $this->client->request('GET', '/api/projects/' . $response['project']['id']);
        $this->assertResponseIsSuccessful();
    }

    /**
     * TEST 5 : NoSQL injection bloquée sur MongoDB (Snippet)
     * Route utilisée : POST /api/snippets
     */
    public function testNoSqlInjectionIsBlocked(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com');
        
        // ACT : Tentative d'injection NoSQL dans le titre du snippet
        $noSqlPayload = '{"$ne": null}';
        
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/snippets', [
            'title' => $noSqlPayload,
            'language' => 'javascript',
            'code' => 'console.log("test");'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        
        // Le payload est traité comme une string normale
        $this->assertEquals($noSqlPayload, $response['title']);
        
        // Vérifier que le snippet existe bien
        $this->client->request('GET', '/api/snippets/' . $response['id']);
        $this->assertResponseIsSuccessful();
    }

    /**
     * TEST 6 : Données trop longues sont refusées (validation)
     * Route utilisée : POST /api/projects/{id}/tasks
     * Contrainte Task: title max 255 caractères
     */
    public function testOversizedInputIsRejected(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT : Titre de tâche trop long (>255 caractères selon Task entity)
        $oversizedTitle = str_repeat('A', 300);
        
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/projects/' . $project->getId() . '/tasks', [
            'title' => $oversizedTitle,
            'status' => 'todo',
            'position' => 1
        ]);
        
        // ASSERT : Doit être refusé (400 Bad Request)
        $this->assertResponseStatusCodeSame(400);
        
        $response = $this->getJsonResponse();
        
        // Ton TaskController retourne soit 'error' soit 'errors'
        $hasError = isset($response['error']) || isset($response['errors']);
        
        $this->assertTrue(
            $hasError,
            'La réponse doit contenir une clé "error" ou "errors" avec le message de validation'
        );
        
        // Si c'est 'errors' (validation Symfony), vérifier que c'est un array
        if (isset($response['errors'])) {
            $this->assertIsArray($response['errors']);
            $this->assertNotEmpty($response['errors']);
        }
    }

    /**
     * TEST 7 : Null bytes injection bloquée
     * Route utilisée : POST /api/snippets
     */
    public function testNullByteInjectionIsBlocked(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com');
        
        // ACT : Tentative d'injection null byte
        $nullBytePayload = "Code malveillant\x00.txt";
        
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/snippets', [
            'title' => 'Test Null Byte',
            'language' => 'text',
            'code' => $nullBytePayload
        ]);
        
        // ASSERT : Soit accepté et stocké tel quel, soit refusé
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        if ($statusCode === 201) {
            // Si accepté, le null byte doit être stocké sans causer de problème
            $response = $this->getJsonResponse();
            $this->assertIsString($response['code']);
        } else {
            // Si refusé, c'est aussi acceptable pour la sécurité
            $this->assertContains($statusCode, [400, 422]);
        }
    }

    /**
     * TEST 8 : Path traversal bloqué dans les noms de fichiers
     * Route utilisée : POST /api/snippets
     */
    public function testPathTraversalIsBlocked(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com');
        
        // ACT : Tentative de path traversal dans le titre
        $pathTraversalPayload = '../../etc/passwd';
        
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/snippets', [
            'title' => $pathTraversalPayload,
            'language' => 'text',
            'code' => 'Test path traversal'
        ]);
        
        // ASSERT : Le snippet est créé mais le path est traité comme string
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        
        // Le path est stocké comme une simple string
        $this->assertEquals($pathTraversalPayload, $response['title']);
        
        // Vérifier qu'aucun fichier système n'a été accédé
        // (Le snippet existe normalement en BDD, pas sur le filesystem)
        $this->client->request('GET', '/api/snippets/' . $response['id']);
        $this->assertResponseIsSuccessful();
    }
}