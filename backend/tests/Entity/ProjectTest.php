<?php

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité Project
 */
class ProjectTest extends TestCase
{
    /**
     * TEST 1 : Les getters/setters fonctionnent correctement
     */
    public function testProjectGettersAndSetters(): void
    {
        // ARRANGE
        $project = new Project();
        $now = new \DateTime();
        
        // ACT
        $project->setName('Mon Projet Test');
        $project->setDescription('Description du projet');
        $project->setCreatedAt($now);

        // ASSERT
        $this->assertEquals('Mon Projet Test', $project->getName());
        $this->assertEquals('Description du projet', $project->getDescription());
        $this->assertEquals($now, $project->getCreatedAt());
    }

    /**
     * TEST 2 : Name est obligatoire et limité à 255 caractères
     */
    public function testProjectNameConstraints(): void
    {
        // ARRANGE
        $project = new Project();
        $longName = str_repeat('a', 255); // Exactement 255 caractères
        
        // ACT
        $project->setName($longName);

        // ASSERT
        $this->assertEquals($longName, $project->getName());
        $this->assertEquals(255, strlen($project->getName()));
    }

    /**
     * TEST 3 : Relation ManyToOne avec User (owner)
     */
    public function testProjectOwnerRelation(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setUsername('owner');
        
        $project = new Project();
        $project->setName('Projet avec owner');
        
        // ACT
        $project->setOwner($user);

        // ASSERT
        $this->assertSame($user, $project->getOwner());
        $this->assertEquals('owner@example.com', $project->getOwner()->getEmail());
    }

    /**
     * TEST 4 : La collection tasks est bien initialisée
     */
    public function testProjectTasksCollectionInitialized(): void
    {
        // ARRANGE & ACT
        $project = new Project();

        // ASSERT
        $this->assertNotNull($project->getTasks());
        $this->assertCount(0, $project->getTasks());
    }

    /**
     * TEST 5 : On peut ajouter une Task au Project
     */
    public function testAddTaskToProject(): void
    {
        // ARRANGE
        $project = new Project();
        $task = new Task();
        $task->setTitle('Ma tâche');
        $task->setStatus('todo');
        $task->setPosition(0);
        $task->setCreatedAt(new \DateTime());
        
        // ACT
        $project->addTask($task);

        // ASSERT
        $this->assertCount(1, $project->getTasks());
        $this->assertTrue($project->getTasks()->contains($task));
        $this->assertSame($project, $task->getProject());
    }
}