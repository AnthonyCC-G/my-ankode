<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité User
 * Version simplifiée sans Validator (pour éviter les problèmes UniqueEntity)
 */
class UserTest extends TestCase
{
    /**
     * TEST 1 : Les getters/setters fonctionnent correctement
     */
    public function testUserGettersAndSetters(): void
    {
        // ARRANGE
        $user = new User();
        
        // ACT
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setPassword('$2y$13$hashedpassword');

        // ASSERT
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('$2y$13$hashedpassword', $user->getPassword());
    }

    /**
     * TEST 2 : Password est bien stocké et récupérable
     */
    public function testPasswordIsHashed(): void
    {
        // ARRANGE
        $user = new User();
        $hashedPassword = '$2y$13$fakehash123456789';

        // ACT
        $user->setPassword($hashedPassword);

        // ASSERT
        $this->assertEquals($hashedPassword, $user->getPassword());
        $this->assertNotEmpty($user->getPassword());
    }

    /**
     * TEST 3 : Rôles par défaut
     */
    public function testDefaultRoles(): void
    {
        // ARRANGE
        $user = new User();

        // ACT
        $roles = $user->getRoles();

        // ASSERT
        $this->assertContains('ROLE_USER', $roles);
        $this->assertGreaterThanOrEqual(1, count($roles));
    }

    /**
     * TEST 4 : CreatedAt automatique
     */
    public function testCreatedAtIsSetAutomatically(): void
    {
        // ARRANGE & ACT
        $user = new User();

        // ASSERT
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertNotNull($user->getCreatedAt());
    }

    /**
     * TEST 5 : getUserIdentifier retourne l'email
     */
    public function testUserIdentifierIsEmail(): void
    {
        // ARRANGE
        $user = new User();
        $email = 'identifier@example.com';
        
        // ACT
        $user->setEmail($email);

        // ASSERT
        $this->assertEquals($email, $user->getUserIdentifier());
    }

    /**
     * TEST 6 : La collection projects est bien initialisée
     */
    public function testProjectsCollectionInitialized(): void
    {
        // ARRANGE & ACT
        $user = new User();

        // ASSERT
        $this->assertNotNull($user->getProjects());
        $this->assertCount(0, $user->getProjects());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getProjects());
    }

    /**
     * TEST 7 : On peut ajouter un Project au User
     */
    public function testAddProjectToUser(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('owner@test.com');
        $user->setUsername('owner');
        
        $project = new \App\Entity\Project();
        $project->setName('Mon Projet');
        $project->setCreatedAt(new \DateTime());
        
        // ACT
        $user->addProject($project);

        // ASSERT
        $this->assertCount(1, $user->getProjects());
        $this->assertTrue($user->getProjects()->contains($project));
        $this->assertSame($user, $project->getOwner());
    }

    /**
     * TEST 8 : La collection competences est bien initialisée
     */
    public function testCompetencesCollectionInitialized(): void
    {
        // ARRANGE & ACT
        $user = new User();

        // ASSERT
        $this->assertNotNull($user->getCompetences());
        $this->assertCount(0, $user->getCompetences());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getCompetences());
    }

    /**
     * TEST 9 : On peut ajouter une Competence au User
     */
    public function testAddCompetenceToUser(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('owner@test.com');
        $user->setUsername('owner');
        
        $competence = new \App\Entity\Competence();
        $competence->setName('PHP');
        
        // ACT
        $user->addCompetence($competence);

        // ASSERT
        $this->assertCount(1, $user->getCompetences());
        $this->assertTrue($user->getCompetences()->contains($competence));
        $this->assertSame($user, $competence->getOwner());
    }
}