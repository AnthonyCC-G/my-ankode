<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Tests\ApiTestCase;

/**
 * Tests de sécurité : Ownership (403 Forbidden)
 * 
 * Vérifie qu'un utilisateur ne peut PAS accéder aux données d'un autre utilisateur.
 */
class OwnershipTest extends ApiTestCase
{
    private User $alice;
    private User $marie;
    private Project $projectAlice;
    private Project $projectMarie;
    private Task $taskAlice;
    private Task $taskMarie;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Récupérer les users des fixtures
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->alice = $userRepository->findOneBy(['email' => 'alice@test.com']);
        $this->marie = $userRepository->findOneBy(['email' => 'marie@test.com']);
        
        // Récupérer un projet d'Alice et un de Marie
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $this->projectAlice = $projectRepository->findOneBy(['owner' => $this->alice]);
        $this->projectMarie = $projectRepository->findOneBy(['owner' => $this->marie]);
        
        // Récupérer une task d'Alice et une de Marie
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $this->taskAlice = $taskRepository->findOneBy(['project' => $this->projectAlice]);
        $this->taskMarie = $taskRepository->findOneBy(['project' => $this->projectMarie]);
    }

    /**
     * Test 1 : Marie ne peut PAS voir les tasks d'Alice (GET → 403)
     * 
     * Scénario :
     * - Marie se connecte
     * - Marie essaie d'accéder à GET /api/tasks/{taskAlice}
     * - Résultat attendu : 403 Forbidden
     * 
     * Note : GET ne nécessite pas de CSRF, aucun changement
     */
    public function testUserCannotAccessOtherUserTask(): void
    {
        // Arrange : Marie se connecte
        $this->loginUser($this->marie);
        
        // Act : Marie essaie de voir la task d'Alice
        $this->client->request('GET', '/api/tasks/' . $this->taskAlice->getId());
        
        // Assert : 403 Forbidden
        $this->assertResponseStatusCode(403);
    }

    /**
     * Test 2 : Marie ne peut PAS modifier le project d'Alice (PUT → 403)
     * 
     * Scénario :
     * - Marie se connecte
     * - Marie essaie de modifier PUT /api/projects/{projectAlice}
     * - Résultat attendu : 403 Forbidden
     * 
     * Utilise apiRequest() pour gérer le CSRF automatiquement
     */
    public function testUserCannotUpdateOtherUserProject(): void
    {
        // Arrange : Marie se connecte
        $this->loginUser($this->marie);
        
        // Act : Marie essaie de modifier le project d'Alice
        // apiRequest (avec CSRF)
        $this->apiRequest('PUT', '/api/projects/' . $this->projectAlice->getId(), [
            'name' => 'Projet piraté par Marie',
            'description' => 'Tentative de modification'
        ]);
        
        // Assert : 403 Forbidden
        $this->assertResponseStatusCode(403);
    }

    /**
     * Test 3 : Marie ne peut PAS supprimer la task d'Alice (DELETE → 403)
     * 
     * Scénario :
     * - Marie se connecte
     * - Marie essaie de supprimer DELETE /api/tasks/{taskAlice}
     * - Résultat attendu : 403 Forbidden
     * 
     * Utilise apiRequest() pour gérer le CSRF automatiquement
     */
    public function testUserCannotDeleteOtherUserTask(): void
    {
        // Arrange : Marie se connecte
        $this->loginUser($this->marie);
        
        // Act : Marie essaie de supprimer la task d'Alice
        // client->request → apiRequest (avec CSRF)
        $this->apiRequest('DELETE', '/api/tasks/' . $this->taskAlice->getId());
        
        // Assert : 403 Forbidden
        $this->assertResponseStatusCode(403);
    }

    /**
     * Test 4 : Marie ne peut PAS créer une task dans le project d'Alice (POST → 403)
     * 
     * Scénario :
     * - Marie se connecte
     * - Marie essaie de créer POST /api/projects/{projectAlice}/tasks
     * - Résultat attendu : 403 Forbidden
     * 
     * Utilise apiRequest() pour gérer le CSRF automatiquement
     */
    public function testUserCannotCreateTaskInOtherUserProject(): void
    {
        // Arrange : Marie se connecte
        $this->loginUser($this->marie);
        
        // Act : Marie essaie de créer une task dans le project d'Alice
        // apiRequest (avec CSRF)
        $this->apiRequest('POST', '/api/projects/' . $this->projectAlice->getId() . '/tasks', [
            'title' => 'Task piratée par Marie',
            'description' => 'Tentative de création',
            'status' => 'todo'
        ]);
        
        // Assert : 403 Forbidden
        $this->assertResponseStatusCode(403);
    }
}