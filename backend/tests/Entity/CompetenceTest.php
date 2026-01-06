<?php

namespace App\Tests\Entity;

use App\Entity\Competence;
use App\Entity\User;
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
        $competence->setLevel(4);
        $competence->setNotes('Expert en Symfony');
        $competence->setProjectsLinks('https://github.com/project1');
        $competence->setSnippetsLinks('https://gist.github.com/snippet1');

        // ASSERT
        $this->assertEquals('PHP', $competence->getName());
        $this->assertEquals(4, $competence->getLevel());
        $this->assertEquals('Expert en Symfony', $competence->getNotes());
        $this->assertEquals('https://github.com/project1', $competence->getProjectsLinks());
        $this->assertEquals('https://gist.github.com/snippet1', $competence->getSnippetsLinks());
    }

    /**
     * TEST 2 : Name est obligatoire et limité à 100 caractères
     */
    public function testCompetenceNameConstraints(): void
    {
        // ARRANGE
        $competence = new Competence();
        $longName = str_repeat('a', 100); // Exactement 100 caractères
        
        // ACT
        $competence->setName($longName);

        // ASSERT
        $this->assertEquals($longName, $competence->getName());
        $this->assertEquals(100, strlen($competence->getName()));
    }

    /**
     * TEST 3 : Level doit être entre 1 et 5
     */
    public function testCompetenceLevelRange(): void
    {
        // ARRANGE
        $competence = new Competence();

        // ACT & ASSERT pour level 1 (minimum)
        $competence->setLevel(1);
        $this->assertEquals(1, $competence->getLevel());

        // ACT & ASSERT pour level 3 (milieu)
        $competence->setLevel(3);
        $this->assertEquals(3, $competence->getLevel());

        // ACT & ASSERT pour level 5 (maximum)
        $competence->setLevel(5);
        $this->assertEquals(5, $competence->getLevel());
    }

    /**
     * TEST 4 : Relation ManyToOne avec User (owner) et createdAt auto
     */
    public function testCompetenceOwnerRelationAndCreatedAt(): void
    {
        // ARRANGE
        $user = new User();
        $user->setEmail('developer@example.com');
        $user->setUsername('developer');
        
        $competence = new Competence();
        $competence->setName('JavaScript');
        $competence->setLevel(4);
        
        // ACT
        $competence->setOwner($user);

        // ASSERT - Relation owner
        $this->assertSame($user, $competence->getOwner());
        $this->assertEquals('developer@example.com', $competence->getOwner()->getEmail());
        
        // ASSERT - CreatedAt auto (défini dans le constructeur)
        $this->assertInstanceOf(\DateTimeImmutable::class, $competence->getCreatedAt());
        $this->assertNotNull($competence->getCreatedAt());
    }
}