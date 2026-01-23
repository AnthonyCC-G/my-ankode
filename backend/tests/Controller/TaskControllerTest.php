<?php

namespace App\Tests\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Tests\ApiTestCase;

class TaskControllerTest extends ApiTestCase
{
    /**
     * Test 1 : GET /api/projects/{id}/tasks
     * Doit retourner la liste des tâches d'un projet
     */
    public function testGetTasksSuccess(): void
    {
        // Arrange : Préparation des données
        $user = $this->createUser('john@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet Test');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        
        $task1 = new Task();
        $task1->setTitle('Tâche 1');
        $task1->setDescription('Description 1');
        $task1->setStatus('todo');
        $task1->setPosition(1);
        $task1->setCreatedAt(new \DateTime());
        $task1->setProject($project);
        $this->entityManager->persist($task1);
        
        $task2 = new Task();
        $task2->setTitle('Tâche 2');
        $task2->setStatus('in_progress');
        $task2->setPosition(2);
        $task2->setCreatedAt(new \DateTime());
        $task2->setProject($project);
        $this->entityManager->persist($task2);
        
        // FLUSH pour sauvegarder en BDD
        $this->entityManager->flush();
        
        // Sauvegarde l'ID avant clear
        $projectId = $project->getId();
        
        // Clear le cache Doctrine
        $this->entityManager->clear();
        
        // Act : Connexion et appel API
        $this->loginUser($user);
        $this->client->request('GET', '/api/projects/' . $projectId . '/tasks');
        
        // Assert : Vérifications
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        
        $response = $this->getJsonResponse();
        $this->assertCount(2, $response);
        $this->assertEquals('Tâche 1', $response[0]['title']);
        $this->assertEquals('todo', $response[0]['status']);
        $this->assertEquals('Tâche 2', $response[1]['title']);
    }

    /**
     * Test 2 : GET /api/projects/{id}/tasks
     * Doit retourner 403 si le projet appartient à un autre user
     */
    public function testGetTasksForbiddenForOtherUser(): void
    {
        // Arrange
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $project = new Project();
        $project->setName('Projet du owner');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // Act : Connexion avec un autre user
        $this->loginUser($otherUser);
        $this->client->request('GET', '/api/projects/' . $project->getId() . '/tasks');
        
        // Assert : Doit être refusé
        $this->assertResponseStatusCodeSame(403);
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Accès refusé', $response['error']);
    }

    /**
     * Test 3 : GET /api/tasks/{id}
     * Doit retourner une tâche spécifique
     */
    public function testGetTaskSuccess(): void
    {
        // Arrange
        $user = $this->createUser('john@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        
        $task = new Task();
        $task->setTitle('Ma Tâche');
        $task->setDescription('Description détaillée');
        $task->setStatus('in_progress');
        $task->setPosition(5);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        // Act
        $this->loginUser($user);
        $this->client->request('GET', '/api/tasks/' . $task->getId());
        
        // Assert
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertEquals('Ma Tâche', $response['title']);
        $this->assertEquals('Description détaillée', $response['description']);
        $this->assertEquals('in_progress', $response['status']);
        $this->assertEquals(5, $response['position']);
        $this->assertArrayHasKey('projectName', $response);
    }

    /**
     * Test 4 : POST /api/projects/{id}/tasks
     * Doit créer une nouvelle tâche
     * Protection CSRF
     */
    public function testCreateTaskSuccess(): void
    {
        // Arrange
        $user = $this->createUser('john@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // Act
        $this->loginUser($user);
        // apiRequest (avec CSRF automatique)
        $this->apiRequest('POST', '/api/projects/' . $project->getId() . '/tasks', [
            'title' => 'Nouvelle tâche',
            'description' => 'Description test',
            'status' => 'todo',
            'position' => 1
        ]);
        
        // Assert
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertEquals('Nouvelle tâche', $response['task']['title']);
        $this->assertEquals('todo', $response['task']['status']);
    }

    /**
     * Test 5 : PATCH /api/tasks/{id}/status
     * Doit changer le statut d'une tâche
     * Protection CSRF
     */
    public function testUpdateTaskStatusSuccess(): void
    {
        // Arrange
        $user = $this->createUser('john@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        
        $task = new Task();
        $task->setTitle('Ma Tâche');
        $task->setStatus('todo');
        $task->setPosition(1);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        // Act
        $this->loginUser($user);
        // apiRequest (avec CSRF automatique)
        $this->apiRequest('PATCH', '/api/tasks/' . $task->getId() . '/status', [
            'status' => 'done'
        ]);
        
        // Assert
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertEquals('done', $response['task']['status']);
    }

    /**
     * Test 6 : PUT /api/tasks/{id}
     * Doit modifier une tâche complète
     * Protection CSRF
     */
    public function testUpdateTaskSuccess(): void
    {
        // Arrange
        $user = $this->createUser('john@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        
        $task = new Task();
        $task->setTitle('Ancienne tâche');
        $task->setDescription('Ancienne description');
        $task->setStatus('todo');
        $task->setPosition(1);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        // Act
        $this->loginUser($user);
        // apiRequest (avec CSRF automatique)
        $this->apiRequest('PUT', '/api/tasks/' . $task->getId(), [
            'title' => 'Tâche modifiée',
            'description' => 'Nouvelle description',
            'status' => 'in_progress',
            'position' => 10
        ]);
        
        // Assert
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertEquals('Tâche modifiée', $response['task']['title']);
        $this->assertEquals('in_progress', $response['task']['status']);
    }

    /**
     * Test 7 : DELETE /api/tasks/{id}
     * Doit supprimer une tâche
     * Protection CSRF
     */
    public function testDeleteTaskSuccess(): void
    {
        // Arrange
        $user = $this->createUser('john@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        
        $task = new Task();
        $task->setTitle('Tâche à supprimer');
        $task->setStatus('todo');
        $task->setPosition(1);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        $taskId = $task->getId();
        
        // Act
        $this->loginUser($user);
        // client->request → apiRequest (avec CSRF automatique)
        $this->apiRequest('DELETE', '/api/tasks/' . $taskId);
        
        // Assert
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        
        // Vérifie que la tâche n'existe plus en BDD
        $deletedTask = $this->entityManager->getRepository(Task::class)->find($taskId);
        $this->assertNull($deletedTask);
    }
}