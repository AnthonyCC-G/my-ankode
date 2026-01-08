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
 * Référentiel DWWM : "Réaliser les tests de sécurité. Les composants métier sont sécurisés."
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
     * Test 2 : POST /api/projects sans login → 401 Unauthorized
     * 
     * Scénario :
     * - Utilisateur NON connecté
     * - Essaie de créer POST /api/projects
     * - Résultat attendu : 401 Unauthorized (ou redirect 302)
     */
    public function testCreateProjectWithoutLogin(): void
    {
        // Act : Requête POST sans login
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'Projet sans auth',
            'description' => 'Tentative de création'
        ]);
        
        // Assert : 401 Unauthorized OU 302 Redirect
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $statusCode === 401 || $statusCode === 302,
            "Expected 401 or 302, got {$statusCode}"
        );
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
     */
    public function testDeleteNonExistentProject(): void
    {
        // Arrange : Alice se connecte
        $this->loginUser($this->alice);
        
        // Act : Alice essaie de supprimer un project inexistant
        $this->client->request('DELETE', '/api/projects/999999');
        
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
        
        // Act : Alice modifie seulement le title (pas description)
        $this->jsonRequest('PUT', '/api/tasks/' . $task->getId(), [
            'title' => 'Nouveau titre modifié'
            // 'description' est ABSENT volontairement
        ]);
        
        // Assert : 200 OK
        $this->assertResponseStatusCode(200);
        
        // Vérifier que la description n'a PAS changé
        $this->entityManager->refresh($task);
        $this->assertEquals('Nouveau titre modifié', $task->getTitle());
        $this->assertEquals($originalDescription, $task->getDescription());
    }
}