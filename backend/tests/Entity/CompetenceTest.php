<?php

namespace App\Tests\Entity;

use App\Entity\Competence;
use App\Entity\User;
use App\Entity\Project;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité Competence
 */
class CompetenceTest extends TestCase
{
    /**
     * TEST 1 : Les getters/setters fonctionnent correctement
     */
    public function testCompetenceGettersAndSetters(): void
    {
        // ARRANGE
        $competence = new Competence();
        
        // ACT
        $competence->setName('PHP');
        $competence->setDescription('Langage de programmation backend');

        // ASSERT
        $this->assertEquals('PHP', $competence->getName());
        $this->assertEquals('Langage de programmation backend', $competence->getDescription());
    }

    /**
     * TEST 2 : Name est obligatoire et limité à 100 caractères
     */
    public function testCompetenceNameConstraints(): void
    {
        // ARRANGE
        $competence = new Competence();
        $longName = str_repeat('a', 100);
        
        // ACT
        $competence->setName($longName);

        // ASSERT
        $this->assertEquals($longName, $competence->getName());
        $this->assertEquals(100, strlen($competence->getName()));
    }

    /**
     * TEST 3 : Relation ManyToOne avec User (owner)
     */
    public function testCompetenceOwnerRelation(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setUsername('owner');
        
        $competence = new Competence();
        $competence->setName('Symfony');
        
        // ACT
        $competence->setOwner($user);

        // ASSERT
        $this->assertSame($user, $competence->getOwner());
        $this->assertEquals('owner@example.com', $competence->getOwner()->getEmail());
    }

    /**
     * TEST 4 : La collection projects est bien initialisée
     */
    public function testCompetenceProjectsCollectionInitialized(): void
    {
        // ARRANGE & ACT
        $competence = new Competence();

        // ASSERT
        $this->assertNotNull($competence->getProjects());
        $this->assertCount(0, $competence->getProjects());
    }

    /**
     * TEST 5 : On peut ajouter un Project à une Competence
     */
    public function testAddProjectToCompetence(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('owner@test.com');
        $user->setUsername('owner');
        
        $project = new Project();
        $project->setName('Mon Projet PHP');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        
        $competence = new Competence();
        $competence->setName('PHP');
        
        // ACT
        $competence->addProject($project);

        // ASSERT
        $this->assertCount(1, $competence->getProjects());
        $this->assertTrue($competence->getProjects()->contains($project));
    }

    /**
     * TEST 6 : snippetsIds est un array vide par défaut
     */
    public function testSnippetsIdsDefaultValue(): void
    {
        // ARRANGE & ACT
        $competence = new Competence();

        // ASSERT
        $this->assertIsArray($competence->getSnippetsIds());
        $this->assertCount(0, $competence->getSnippetsIds());
    }

    /**
     * TEST 7 : On peut ajouter un snippetId
     */
    public function testAddSnippetId(): void
    {
        // ARRANGE
        $competence = new Competence();
        $snippetId = '507f1f77bcf86cd799439011';
        
        // ACT
        $competence->addSnippetId($snippetId);

        // ASSERT
        $this->assertCount(1, $competence->getSnippetsIds());
        $this->assertContains($snippetId, $competence->getSnippetsIds());
    }

    /**
     * TEST 8 : calculateLevel() retourne 0 par défaut
     */
    public function testCalculateLevelDefault(): void
    {
        // ARRANGE
        $competence = new Competence();
        $competence->setName('JavaScript');
        
        // ACT
        $competence->calculateLevel();

        // ASSERT
        $this->assertEquals(0.0, $competence->getLevel());
    }

    /**
     * TEST 9 : calculateLevel() avec 1 projet = 1.0
     */
    public function testCalculateLevelWithOneProject(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('owner@test.com');
        $user->setUsername('owner');
        
        $project = new Project();
        $project->setName('Projet Test');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        
        $competence = new Competence();
        $competence->setName('React');
        $competence->addProject($project);
        
        // ACT
        $competence->calculateLevel();

        // ASSERT
        $this->assertEquals(1.0, $competence->getLevel());
    }

    /**
     * TEST 10 : calculateLevel() avec 1 snippet = 0.5
     */
    public function testCalculateLevelWithOneSnippet(): void
    {
        // ARRANGE
        $competence = new Competence();
        $competence->setName('Python');
        $competence->addSnippetId('507f1f77bcf86cd799439011');
        
        // ACT
        $competence->calculateLevel();

        // ASSERT
        $this->assertEquals(0.5, $competence->getLevel());
    }

    /**
     * TEST 11 : calculateLevel() plafonné à 5.0
     */
    public function testCalculateLevelCappedAtFive(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('owner@test.com');
        $user->setUsername('owner');
        
        $competence = new Competence();
        $competence->setName('Docker');
        
        // Ajouter 10 projets (10 * 1.0 = 10.0)
        for ($i = 0; $i < 10; $i++) {
            $project = new Project();
            $project->setName("Projet $i");
            $project->setOwner($user);
            $project->setCreatedAt(new \DateTime());
            $competence->addProject($project);
        }
        
        // ACT
        $competence->calculateLevel();

        // ASSERT
        $this->assertEquals(5.0, $competence->getLevel());
    }

    /**
     * TEST 12 : CreatedAt automatique
     */
    public function testCreatedAtIsSetAutomatically(): void
    {
        // ARRANGE & ACT
        $competence = new Competence();

        // ASSERT
        $this->assertInstanceOf(\DateTimeImmutable::class, $competence->getCreatedAt());
        $this->assertNotNull($competence->getCreatedAt());
    }
}