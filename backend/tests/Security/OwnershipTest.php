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
    private User $bob;
    private Project $projectAlice;
    private Project $projectBob;
    private Task $taskAlice;
    private Task $taskBob;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Récupérer les users des fixtures
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->alice = $userRepository->findOneBy(['email' => 'alice@test.com']);
        $this->bob = $userRepository->findOneBy(['email' => 'bob@test.com']);
        
        // Récupérer un projet d'Alice et un de Bob
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $this->projectAlice = $projectRepository->findOneBy(['owner' => $this->alice]);
        $this->projectBob = $projectRepository->findOneBy(['owner' => $this->bob]);
        
        // Récupérer une task d'Alice et une de Bob
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $this->taskAlice = $taskRepository->findOneBy(['project' => $this->projectAlice]);
        $this->taskBob = $taskRepository->findOneBy(['project' => $this->projectBob]);
    }

    /**
     * Test 1 : Bob ne peut PAS voir les tasks d'Alice (GET → 403)
     */
    public function testUserCannotAccessOtherUserTask(): void
    {
        $this->loginUser($this->bob);
        $this->client->request('GET', '/api/tasks/' . $this->taskAlice->getId());
        $this->assertResponseStatusCode(403);
    }

    /**
     * Test 2 : Bob ne peut PAS modifier le project d'Alice (PUT → 403)
     */
    public function testUserCannotUpdateOtherUserProject(): void
    {
        $this->loginUser($this->bob);
        $this->apiRequest('PUT', '/api/projects/' . $this->projectAlice->getId(), [
            'name' => 'Projet piraté par Bob',
            'description' => 'Tentative de modification'
        ]);
        $this->assertResponseStatusCode(403);
    }

    /**
     * Test 3 : Bob ne peut PAS supprimer la task d'Alice (DELETE → 403)
     */
    public function testUserCannotDeleteOtherUserTask(): void
    {
        $this->loginUser($this->bob);
        $this->apiRequest('DELETE', '/api/tasks/' . $this->taskAlice->getId());
        $this->assertResponseStatusCode(403);
    }

    /**
     * Test 4 : Bob ne peut PAS créer une task dans le project d'Alice (POST → 403)
     */
    public function testUserCannotCreateTaskInOtherUserProject(): void
    {
        $this->loginUser($this->bob);
        $this->apiRequest('POST', '/api/projects/' . $this->projectAlice->getId() . '/tasks', [
            'title' => 'Task piratée par Bob',
            'description' => 'Tentative de création',
            'status' => 'todo'
        ]);
        $this->assertResponseStatusCode(403);
    }
}