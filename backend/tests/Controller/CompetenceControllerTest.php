<?php

namespace App\Tests\Controller;

use App\Entity\Competence;
use App\Entity\Project;
use App\Tests\ApiTestCase;

/**
 * Tests fonctionnels pour CompetenceController (API REST)
 * Teste les routes CRUD + protection CSRF + ownership
 */
class CompetenceControllerTest extends ApiTestCase
{
    /**
     * TEST 1 : GET /api/competences
     * Doit retourner la liste des competences de l'utilisateur connecté
     */
    public function testGetCompetencesSuccess(): void
    {
        // ARRANGE (Prépare)
        $user = $this->createUser('john@test.com');
        
        $competence1 = new Competence();
        $competence1->setName('PHP');
        $competence1->setDescription('Langage backend');
        $competence1->setOwner($user);
        $this->entityManager->persist($competence1);
        
        $competence2 = new Competence();
        $competence2->setName('Symfony');
        $competence2->setDescription('Framework PHP');
        $competence2->setOwner($user);
        $this->entityManager->persist($competence2);
        
        $this->entityManager->flush();
        
        // ACT (FAIT)
        $this->loginUser($user);
        $this->client->request('GET', '/api/competences');
        
        // ASSERT (RESULTAT)
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        
        $response = $this->getJsonResponse();
        $this->assertCount(2, $response);
        // Vérifier que les 2 compétences sont présentes (ordre non garanti)
        $names = array_column($response, 'name');
        $this->assertContains('PHP', $names);
        $this->assertContains('Symfony', $names);
    }

    /**
     * TEST 2 : GET /api/competences/{id}
     * Doit retourner une competence spécifique avec le level calculé
     */
    public function testGetCompetenceSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        $competence = new Competence();
        $competence->setName('Docker');
        $competence->setDescription('Conteneurisation');
        $competence->setOwner($user);
        $competence->calculateLevel();
        $this->entityManager->persist($competence);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($user);
        $this->client->request('GET', '/api/competences/' . $competence->getId());
        
        // ASSERT
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertEquals('Docker', $response['name']);
        $this->assertEquals('Conteneurisation', $response['description']);
        $this->assertEquals(0.0, $response['level']);
        $this->assertArrayHasKey('projects', $response);
        $this->assertArrayHasKey('snippetsIds', $response);
    }

    /**
     * TEST 3 : POST /api/competences
     * Doit créer une nouvelle competence avec CSRF
     */
    public function testCreateCompetenceSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/competences', [
            'name' => 'React',
            'description' => 'Bibliothèque JavaScript'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertEquals('React', $response['competence']['name']);
        $this->assertEquals(0.0, $response['competence']['level']);
    }

    /**
     * TEST 4 : POST /api/competences sans name
     * Doit retourner 400 Bad Request
     */
    public function testCreateCompetenceWithoutNameFails(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('POST', '/api/competences', [
            'description' => 'Sans nom'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(400);
        
        $response = $this->getJsonResponse();
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * TEST 5 : PUT /api/competences/{id}
     * Doit modifier une competence et recalculer le level
     */
    public function testUpdateCompetenceSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        
        $competence = new Competence();
        $competence->setName('Angular');
        $competence->setDescription('Framework frontend');
        $competence->setOwner($user);
        $this->entityManager->persist($competence);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('PUT', '/api/competences/' . $competence->getId(), [
            'name' => 'Angular 18',
            'description' => 'Framework frontend moderne',
            'projectIds' => [$project->getId()]
        ]);
        
        // ASSERT
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertEquals('Angular 18', $response['competence']['name']);
        $this->assertEquals(1.0, $response['competence']['level']); // 1 projet = 1.0
        $this->assertCount(1, $response['competence']['projects']);
    }

    /**
     * TEST 6 : DELETE /api/competences/{id}
     * Doit supprimer une competence
     */
    public function testDeleteCompetenceSuccess(): void
    {
        // ARRANGE
        $user = $this->createUser('john@test.com');
        
        $competence = new Competence();
        $competence->setName('Vue.js');
        $competence->setOwner($user);
        $this->entityManager->persist($competence);
        $this->entityManager->flush();
        
        $competenceId = $competence->getId();
        
        // ACT
        $this->loginUser($user);
        $this->apiRequest('DELETE', '/api/competences/' . $competenceId);
        
        // ASSERT
        $this->assertResponseIsSuccessful();
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        
        // Vérifie que la competence n'existe plus en BDD
        $deletedCompetence = $this->entityManager->getRepository(Competence::class)->find($competenceId);
        $this->assertNull($deletedCompetence);
    }

    /**
     * TEST 7 : GET /api/competences/{id} d'un autre user
     * Doit retourner 403 Forbidden (ownership protection)
     */
    public function testGetCompetenceForbiddenForOtherUser(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $competence = new Competence();
        $competence->setName('Competence du owner');
        $competence->setOwner($owner);
        $this->entityManager->persist($competence);
        $this->entityManager->flush();
        
        // ACT : Connexion avec un autre user
        $this->loginUser($otherUser);
        $this->client->request('GET', '/api/competences/' . $competence->getId());
        
        // ASSERT : Doit être refusé
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 8 : PUT /api/competences/{id} d'un autre user
     * Doit retourner 403 Forbidden
     */
    public function testUpdateCompetenceForbiddenForOtherUser(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $competence = new Competence();
        $competence->setName('Competence du owner');
        $competence->setOwner($owner);
        $this->entityManager->persist($competence);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->apiRequest('PUT', '/api/competences/' . $competence->getId(), [
            'name' => 'Tentative de piratage'
        ]);
        
        // ASSERT
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 9 : DELETE /api/competences/{id} d'un autre user
     * Doit retourner 403 Forbidden
     */
    public function testDeleteCompetenceForbiddenForOtherUser(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $competence = new Competence();
        $competence->setName('Competence du owner');
        $competence->setOwner($owner);
        $this->entityManager->persist($competence);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->apiRequest('DELETE', '/api/competences/' . $competence->getId());
        
        // ASSERT
        $this->assertResponseStatusCodeSame(403);
    }
}