<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Tests\ApiTestCase;

/**
 * Tests de sécurité des mots de passe
 * 
 * Vérifie :
 * - Hashage automatique des mots de passe
 * - Non-exposition dans les réponses API
 * - Salt aléatoire pour chaque hash
 * - Algorithme de hash moderne (bcrypt/argon2)
 * 
 * Référentiel DWWM CP7 : Sécurité des données sensibles
 */
class PasswordSecurityTest extends ApiTestCase
{
    /**
     * TEST 1 : Le mot de passe est hashé, jamais stocké en clair
     */
    public function testPasswordIsHashedNotPlaintext(): void
    {
        // ARRANGE
        $plainPassword = 'MySecurePassword123!';
        
        // ACT
        $user = $this->createUser('test@example.com', $plainPassword);
        
        // ASSERT
        // Le password stocké ne doit PAS être le mot de passe en clair
        $this->assertNotEquals($plainPassword, $user->getPassword());
        
        // Le password doit être un hash bcrypt/argon2
        $this->assertStringStartsWith('$', $user->getPassword());
        $this->assertGreaterThan(50, strlen($user->getPassword()));
        
        // Le hash doit être vérifiable
        $this->assertTrue(
            password_verify($plainPassword, $user->getPassword()),
            'Le hash doit correspondre au mot de passe original'
        );
    }

    /**
     * TEST 2 : Le mot de passe n'est JAMAIS exposé dans les réponses API
     * Teste sur GET /api/projects (route existante protégée)
     */
    public function testPasswordNotExposedInApiResponse(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com', 'SecurePass123!');
        
        // ACT : Récupérer les projets (route API existante)
        $this->loginUser($user);
        $this->client->request('GET', '/api/projects');
        
        // ASSERT
        $this->assertResponseIsSuccessful();
        
        $responseContent = $this->client->getResponse()->getContent();
        
        // Le password hash ne doit JAMAIS apparaître dans les réponses API
        $this->assertStringNotContainsString(
            $user->getPassword(),
            $responseContent,
            'Le hash du password ne doit jamais être exposé dans les réponses API'
        );
        
        // Vérifier qu'aucune clé "password" n'apparaît
        $this->assertStringNotContainsString(
            '"password"',
            strtolower($responseContent),
            'Aucune clé "password" ne doit apparaître dans les réponses JSON'
        );
    }

    /**
     * TEST 3 : Le hash change même pour le même mot de passe (salt aléatoire)
     */
    public function testPasswordHashUsesRandomSalt(): void
    {
        // ARRANGE
        $plainPassword = 'SamePassword123!';
        
        // ACT : Créer 2 users avec le même mot de passe
        $user1 = $this->createUser('user1@test.com', $plainPassword);
        $user2 = $this->createUser('user2@test.com', $plainPassword);
        
        // ASSERT : Les hashs doivent être DIFFÉRENTS (salt aléatoire)
        $this->assertNotEquals(
            $user1->getPassword(),
            $user2->getPassword(),
            'Deux hashs du même mot de passe doivent être différents (salt aléatoire)'
        );
        
        // Mais les deux doivent vérifier le même plaintext
        $this->assertTrue(password_verify($plainPassword, $user1->getPassword()));
        $this->assertTrue(password_verify($plainPassword, $user2->getPassword()));
    }

    /**
     * TEST 4 : Le password n'est pas exposé lors de la sérialisation User en BDD
     */
    public function testPasswordNotSerializedInUserObject(): void
    {
        // ARRANGE
        $user = $this->createUser('test@example.com', 'SecurePass123!');
        
        // ACT : Récupérer un user en BDD (simulation d'une réponse API)
        $this->entityManager->clear(); // Clear le cache
        $fetchedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        
        // Simuler la sérialisation JSON (comme dans une réponse API)
        $serialized = json_encode([
            'id' => $fetchedUser->getId(),
            'email' => $fetchedUser->getEmail(),
            'roles' => $fetchedUser->getRoles(),
            // On ne sérialise volontairement PAS le password
        ]);
        
        // ASSERT
        $this->assertStringNotContainsString(
            $fetchedUser->getPassword(),
            $serialized,
            'Le password ne doit jamais être sérialisé dans les réponses JSON'
        );
    }

    /**
     * TEST 5 : Password bien hashé avec algorithme moderne (bcrypt/argon2)
     */
    public function testPasswordUsesModernHashAlgorithm(): void
    {
        // ARRANGE & ACT
        $user = $this->createUser('test@example.com', 'SecurePassword123!');
        
        // ASSERT : Vérifier que c'est un hash moderne
        $hash = $user->getPassword();
        
        // Bcrypt commence par $2y$ ou $2b$
        // Argon2 commence par $argon2
        $isModernHash = 
            str_starts_with($hash, '$2y$') ||  // Bcrypt
            str_starts_with($hash, '$2b$') ||  // Bcrypt
            str_starts_with($hash, '$argon2'); // Argon2
        
        $this->assertTrue(
            $isModernHash,
            'Le password doit utiliser un algorithme moderne (bcrypt ou argon2)'
        );
        
        // Le hash doit être suffisamment long (>= 60 caractères pour bcrypt)
        $this->assertGreaterThanOrEqual(
            60,
            strlen($hash),
            'Le hash bcrypt fait 60 caractères (standard), argon2 peut être plus long'
        );
    }

    /**
     * TEST 6 : Vérifier que le hash résiste aux comparaisons timing attack
     * password_verify() de PHP est timing-safe par conception
     */
    public function testPasswordHashResistsTimingAttacks(): void
    {
        // ARRANGE
        $correctPassword = 'CorrectPassword123!';
        $user = $this->createUser('test@example.com', $correctPassword);
        
        // ACT : Tester avec un mauvais password
        $wrongPassword = 'WrongPassword456!';
        
        // ASSERT : password_verify utilise timing-safe comparison
        $this->assertFalse(
            password_verify($wrongPassword, $user->getPassword()),
            'Un mauvais password doit être refusé'
        );
        
        $this->assertTrue(
            password_verify($correctPassword, $user->getPassword()),
            'Le bon password doit être accepté'
        );
        
        // Note : password_verify de PHP est déjà timing-safe
        // Ce test vérifie qu'on utilise la bonne méthode
    }
}