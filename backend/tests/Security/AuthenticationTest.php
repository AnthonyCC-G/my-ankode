<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Tests\ApiTestCase;

/**
 * Tests de sécurité : Authentication + Edge Cases
 * 
 * Vérifie :
 * - 401 Unauthorized : accès API sans authentification
 * - 404 Not Found : ressources inexistantes
 * - 200 OK : update partiel fonctionne
 * 
 */
class AuthenticationTest extends ApiTestCase
{
    private User $alice;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Récupérer Alice des fixtures
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->alice = $userRepository->findOneBy(['email' => 'alice@test.com']);
    }

    /**
     * Test 1 : GET /api/projects sans login → 401 Unauthorized
     * 
     * Scénario :
     * - Utilisateur NON connecté
     * - Essaie d'accéder à GET /api/projects
     * - Résultat attendu : 401 Unauthorized (ou redirect 302 vers /login)
     * 
     * Note : GET ne nécessite pas de CSRF, donc le test fonctionne tel quel
     */
    public function testGetProjectsWithoutLogin(): void
    {
        // Act : Requête sans login
        $this->client->request('GET', '/api/projects');
        
        // Assert : 401 Unauthorized OU 302 Redirect vers /login
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $statusCode === 401 || $statusCode === 302,
            "Expected 401 or 302, got {$statusCode}"
        );
    }

    /**
     * Test 2 : POST /api/projects sans login → 400 Bad Request (CSRF manquant)
     * 
     * Scénario :
     * - Utilisateur NON connecté
     * - Essaie de créer POST /api/projects SANS token CSRF
     * - Résultat attendu : 400 Bad Request (Token CSRF manquant)
     * 
     * Le CSRF est vérifié AVANT l'authentification !
     * Un utilisateur non connecté n'a pas de token CSRF, donc il reçoit 400 avant 401.
     * Ce comportement est correct et sécurisé.
     */
    public function testCreateProjectWithoutLogin(): void
    {
        // Act : Requête POST sans login ET sans CSRF
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'Projet sans auth',
            'description' => 'Tentative de création'
        ]);
        
        // Assert : 400 Bad Request (Token CSRF manquant)
        // Le CSRF est vérifié en premier, donc 400 avant 401
        $this->assertResponseStatusCodeSame(400);
        
        // Pas de getJsonResponse() car la réponse est en HTML (page d'erreur)
        // On vérifie juste le status code, c'est suffisant pour ce test
    }

    /**
     * Test 3 : GET task inexistante → 404 Not Found
     * 
     * Scénario :
     * - Alice se connecte
     * - Alice essaie d'accéder à GET /api/tasks/999999 (n'existe pas)
     * - Résultat attendu : 404 Not Found
     */
    public function testGetNonExistentTask(): void
    {
        // Arrange : Alice se connecte
        $this->loginUser($this->alice);
        
        // Act : Alice essaie d'accéder à une task inexistante
        $this->client->request('GET', '/api/tasks/999999');
        
        // Assert : 404 Not Found
        $this->assertResponseStatusCode(404);
    }

    /**
     * Test 4 : DELETE project inexistant → 404 Not Found
     * 
     * Scénario :
     * - Alice se connecte
     * - Alice essaie de supprimer DELETE /api/projects/999999 (n'existe pas)
     * - Résultat attendu : 404 Not Found
     * 
     * Utilise apiRequest() pour gérer le CSRF automatiquement
     */
    public function testDeleteNonExistentProject(): void
    {
        // Arrange : Alice se connecte
        $this->loginUser($this->alice);
        
        // Act : Alice essaie de supprimer un project inexistant
        // client->request → apiRequest (avec CSRF)
        $this->apiRequest('DELETE', '/api/projects/999999');
        
        // Assert : 404 Not Found
        $this->assertResponseStatusCode(404);
    }

    /**
     * Test 5 : PUT task avec données partielles → 200 OK
     * 
     * Scénario :
     * - Alice se connecte
     * - Alice modifie PUT /api/tasks/{id} en envoyant seulement le title (pas description)
     * - Résultat attendu : 200 OK (les champs non envoyés restent inchangés)
     * 
     * Utilise apiRequest() pour gérer le CSRF automatiquement
     */
    public function testUpdateTaskWithPartialData(): void
    {
        // Arrange : Alice se connecte et récupère une de ses tasks
        $this->loginUser($this->alice);
        
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $project = $projectRepository->findOneBy(['owner' => $this->alice]);
        
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->findOneBy(['project' => $project]);
        
        $originalDescription = $task->getDescription();
        $taskId = $task->getId();
        
        // Act : Alice modifie seulement le title (pas description)
        // apiRequest (avec CSRF)
        $this->apiRequest('PUT', '/api/tasks/' . $taskId, [
            'title' => 'Nouveau titre modifié'
            // 'description' est ABSENT volontairement
        ]);
        
        // Assert : 200 OK
        $this->assertResponseStatusCode(200);
        
        // Recharger l'entité avec find() au lieu de refresh()
        $updatedTask = $taskRepository->find($taskId);
        $this->assertEquals('Nouveau titre modifié', $updatedTask->getTitle());
        $this->assertEquals($originalDescription, $updatedTask->getDescription());
    }
}