<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Tests\ApiTestCase;

/**
 * Tests de sécurité : Validation (400 Bad Request)
 * 
 * Vérifie que l'API rejette les données invalides avec un code 400.
 * Référentiel DWWM : "Réaliser les tests de sécurité. Les composants métier sont sécurisés."
 */
class ValidationTest extends ApiTestCase
{
    private User $alice;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Récupérer Alice des fixtures
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->alice = $userRepository->findOneBy(['email' => 'alice@test.com']);
    }

    /**
     * Test 1 : Créer une task SANS title → 400 Bad Request
     * 
     * Scénario :
     * - Alice se connecte
     * - Alice essaie de créer une task sans title
     * - Résultat attendu : 400 Bad Request
     */
    public function testCreateTaskWithoutTitle(): void
    {
        // Arrange : Alice se connecte
        $this->loginUser($this->alice);
        
        // Récupérer un projet d'Alice
        $projectRepository = $this->entityManager->getRepository(\App\Entity\Project::class);
        $project = $projectRepository->findOneBy(['owner' => $this->alice]);
        
        // Act : Alice essaie de créer une task sans title
        $this->jsonRequest('POST', '/api/projects/' . $project->getId() . '/tasks', [
            // 'title' est MANQUANT (obligatoire)
            'description' => 'Description valide',
            'status' => 'todo'
        ]);
        
        // Assert : 400 Bad Request
        $this->assertResponseStatusCode(400);
    }

    /**
     * Test 2 : Créer une task avec status INVALIDE → 400 Bad Request
     * 
     * Scénario :
     * - Alice se connecte
     * - Alice essaie de créer une task avec status='invalid_status'
     * - Résultat attendu : 400 Bad Request
     */
    public function testCreateTaskWithInvalidStatus(): void
    {
        // Arrange : Alice se connecte
        $this->loginUser($this->alice);
        
        // Récupérer un projet d'Alice
        $projectRepository = $this->entityManager->getRepository(\App\Entity\Project::class);
        $project = $projectRepository->findOneBy(['owner' => $this->alice]);
        
        // Act : Alice essaie de créer une task avec status invalide
        $this->jsonRequest('POST', '/api/projects/' . $project->getId() . '/tasks', [
            'title' => 'Task avec status invalide',
            'description' => 'Description valide',
            'status' => 'invalid_status' // Valeurs valides: todo, in_progress, done
        ]);
        
        // Assert : 400 Bad Request
        $this->assertResponseStatusCode(400);
    }

    /**
     * Test 3 : Créer un project SANS name → 400 Bad Request
     * 
     * Scénario :
     * - Alice se connecte
     * - Alice essaie de créer un project sans name
     * - Résultat attendu : 400 Bad Request
     */
    public function testCreateProjectWithoutName(): void
    {
        // Arrange : Alice se connecte
        $this->loginUser($this->alice);
        
        // Act : Alice essaie de créer un project sans name
        $this->jsonRequest('POST', '/api/projects', [
            // 'name' est MANQUANT (obligatoire)
            'description' => 'Description valide'
        ]);
        
        // Assert : 400 Bad Request
        $this->assertResponseStatusCode(400);
    }

    /**
     * Test 4 : Créer une task avec title > 255 caractères → 400 Bad Request
     * 
     * Scénario :
     * - Alice se connecte
     * - Alice essaie de créer une task avec title trop long (256 caractères)
     * - Résultat attendu : 400 Bad Request
     */
    public function testTaskTitleTooLong(): void
    {
        // Arrange : Alice se connecte
        $this->loginUser($this->alice);
        
        // Récupérer un projet d'Alice
        $projectRepository = $this->entityManager->getRepository(\App\Entity\Project::class);
        $project = $projectRepository->findOneBy(['owner' => $this->alice]);
        
        // Créer un title de 256 caractères (limite = 255)
        $tooLongTitle = str_repeat('a', 256);
        
        // Act : Alice essaie de créer une task avec title trop long
        $this->jsonRequest('POST', '/api/projects/' . $project->getId() . '/tasks', [
            'title' => $tooLongTitle, //  256 caractères (max = 255)
            'description' => 'Description valide',
            'status' => 'todo'
        ]);
        
        // Assert : 400 Bad Request
        $this->assertResponseStatusCode(400);
    }
}