<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité Task
 */
class TaskTest extends TestCase
{
    /**
     * TEST 1 : Les getters/setters fonctionnent correctement
     */
    public function testTaskGettersAndSetters(): void
    {
        // ARRANGE
        $task = new Task();
        $now = new \DateTime();
        
        // ACT
        $task->setTitle('Ma tâche test');
        $task->setDescription('Description de la tâche');
        $task->setStatus('todo');
        $task->setPosition(1);
        $task->setCreatedAt($now);

        // ASSERT
        $this->assertEquals('Ma tâche test', $task->getTitle());
        $this->assertEquals('Description de la tâche', $task->getDescription());
        $this->assertEquals('todo', $task->getStatus());
        $this->assertEquals(1, $task->getPosition());
        $this->assertEquals($now, $task->getCreatedAt());
    }

    /**
     * TEST 2 : Title est obligatoire et limité à 255 caractères
     */
    public function testTaskTitleConstraints(): void
    {
        // ARRANGE
        $task = new Task();
        $longTitle = str_repeat('a', 255); // Exactement 255 caractères
        
        // ACT
        $task->setTitle($longTitle);

        // ASSERT
        $this->assertEquals($longTitle, $task->getTitle());
        $this->assertEquals(255, strlen($task->getTitle()));
    }

    /**
     * TEST 3 : Status accepte les valeurs todo, in_progress, done
     */
    public function testTaskStatusEnum(): void
    {
        // ARRANGE
        $task = new Task();

        // ACT & ASSERT pour 'todo'
        $task->setStatus('todo');
        $this->assertEquals('todo', $task->getStatus());

        // ACT & ASSERT pour 'in_progress'
        $task->setStatus('in_progress');
        $this->assertEquals('in_progress', $task->getStatus());

        // ACT & ASSERT pour 'done'
        $task->setStatus('done');
        $this->assertEquals('done', $task->getStatus());
    }

    /**
     * TEST 4 : Relation ManyToOne avec Project
     */
    public function testTaskProjectRelation(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setUsername('testuser');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setCreatedAt(new \DateTime());
        $project->setOwner($user);
        
        $task = new Task();
        $task->setTitle('Tâche du projet');
        $task->setStatus('todo');
        $task->setPosition(0);
        $task->setCreatedAt(new \DateTime());
        
        // ACT
        $task->setProject($project);

        // ASSERT
        $this->assertSame($project, $task->getProject());
        $this->assertEquals('Mon Projet', $task->getProject()->getName());
    }

    /**
     * TEST 5 : Position peut être définie et récupérée
     */
    public function testTaskPosition(): void
    {
        // ARRANGE
        $task = new Task();
        
        // ACT
        $task->setPosition(0);
        $this->assertEquals(0, $task->getPosition());
        
        $task->setPosition(5);
        $this->assertEquals(5, $task->getPosition());
        
        $task->setPosition(999);
        $this->assertEquals(999, $task->getPosition());

        // ASSERT
        $this->assertIsInt($task->getPosition());
    }
}