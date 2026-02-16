<?php

namespace App\Tests\Document;

use App\Document\Snippet;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le document MongoDB Snippet
 * Tests simples sans connexion MongoDB (tests unitaires purs)
 */
class SnippetTest extends TestCase
{
    /**
     * TEST 1 : Les getters/setters fonctionnent correctement
     */
    public function testSnippetGettersAndSetters(): void
    {
        // ARRANGE
        $snippet = new Snippet();
        
        // ACT
        $snippet->setTitle('Connexion MySQL');
        $snippet->setLanguage('php');
        $snippet->setCode('<?php $conn = new PDO(...);');
        $snippet->setDescription('Exemple de connexion à MySQL');
        $snippet->setUserId('123');

        // ASSERT
        $this->assertEquals('Connexion MySQL', $snippet->getTitle());
        $this->assertEquals('php', $snippet->getLanguage());
        $this->assertEquals('<?php $conn = new PDO(...);', $snippet->getCode());
        $this->assertEquals('Exemple de connexion à MySQL', $snippet->getDescription());
        $this->assertEquals('123', $snippet->getUserId());
    }

    /**
     * TEST 2 : Tags est un array vide par défaut
     */
    public function testTagsDefaultValue(): void
    {
        // ARRANGE & ACT
        $snippet = new Snippet();

        // ASSERT
        $this->assertIsArray($snippet->getTags());
        $this->assertCount(0, $snippet->getTags());
    }

    /**
     * TEST 3 : On peut définir des tags
     */
    public function testSetTags(): void
    {
        // ARRANGE
        $snippet = new Snippet();
        $tags = ['php', 'mysql', 'pdo'];
        
        // ACT
        $snippet->setTags($tags);

        // ASSERT
        $this->assertEquals($tags, $snippet->getTags());
        $this->assertCount(3, $snippet->getTags());
        $this->assertContains('php', $snippet->getTags());
        $this->assertContains('mysql', $snippet->getTags());
    }

    /**
     * TEST 4 : CreatedAt est automatiquement défini
     */
    public function testCreatedAtIsSetAutomatically(): void
    {
        // ARRANGE & ACT
        $snippet = new Snippet();

        // ASSERT
        $this->assertInstanceOf(\DateTimeImmutable::class, $snippet->getCreatedAt());
        $this->assertNotNull($snippet->getCreatedAt());
    }

    /**
     * TEST 5 : UserId peut être stocké et récupéré
     */
    public function testUserIdCanBeSet(): void
    {
        // ARRANGE
        $snippet = new Snippet();
        $userId = '507f1f77bcf86cd799439011';
        
        // ACT
        $snippet->setUserId($userId);

        // ASSERT
        $this->assertEquals($userId, $snippet->getUserId());
        $this->assertIsString($snippet->getUserId());
    }

    /**
     * TEST 6 : Title, Language et Code sont bien stockés
     */
    public function testRequiredFieldsAreStored(): void
    {
        // ARRANGE
        $snippet = new Snippet();
        
        // ACT
        $snippet->setTitle('Boucle for en Python');
        $snippet->setLanguage('python');
        $snippet->setCode('for i in range(10):
    print(i)');

        // ASSERT
        $this->assertNotEmpty($snippet->getTitle());
        $this->assertNotEmpty($snippet->getLanguage());
        $this->assertNotEmpty($snippet->getCode());
        $this->assertStringContainsString('for i in range', $snippet->getCode());
    }

    /**
     * TEST 7 : Description peut être null
     */
    public function testDescriptionCanBeNull(): void
    {
        // ARRANGE
        $snippet = new Snippet();
        
        // ACT
        $snippet->setDescription(null);

        // ASSERT
        $this->assertNull($snippet->getDescription());
    }

    /**
     * TEST 8 : On peut définir une description
     */
    public function testDescriptionCanBeSet(): void
    {
        // ARRANGE
        $snippet = new Snippet();
        $description = 'Un snippet très utile pour gérer les connexions';
        
        // ACT
        $snippet->setDescription($description);

        // ASSERT
        $this->assertEquals($description, $snippet->getDescription());
        $this->assertIsString($snippet->getDescription());
    }
}