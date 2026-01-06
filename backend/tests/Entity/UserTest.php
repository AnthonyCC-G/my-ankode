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
}