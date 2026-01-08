<?php

namespace App\Tests\Controller;

use App\Entity\Project;
use App\Tests\ApiTestCase;

class ProjectControllerTest extends ApiTestCase
{
    /**
     * Test 1 : GET /api/projects
     * Doit retourner la liste des projets de l'utilisateur connecté
     */
    public function testGetProjectsSuccess(): void
    {
        // Arrange : Préparation des données
        $user = $this->createUser('project_user@test.com');
        
        $project1 = new Project();
        $project1->setName('Projet 1');
        $project1->setDescription('Description du projet 1');
        $project1->setOwner($user);
        $project1->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project1);
        
        $project2 = new Project();
        $project2->setName('Projet 2');
        $project2->setDescription('Description du projet 2');
        $project2->setOwner($user);
        $project2->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project2);
        
        // FLUSH pour sauvegarder en BDD
        $this->entityManager->flush();
        
        // Clear le cache Doctrine
        $this->entityManager->clear();
        
        // Act : Connexion et appel API
        $this->loginUser($user);
        $this->client->request('GET', '/api/projects');
        
        // Assert : Vérifications
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        
        $response = $this->getJsonResponse();
        $this->assertCount(2, $response);
        $this->assertEquals('Projet 1', $response[0]['name']);
        $this->assertEquals('Description du projet 1', $response[0]['description']);
        $this->assertEquals('Projet 2', $response[1]['name']);
    }

    /**
     * Test 2 : GET /api/projects/{id}
     * Doit retourner un projet spécifique
     */
    public function testGetProjectSuccess(): void
    {
        // Arrange
        $user = $this->createUser('project_owner@test.com');
        
        $project = new Project();
        $project->setName('Mon Super Projet');
        $project->setDescription('Description détaillée du projet');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // Act
        $this->loginUser($user);
        $this->client->request('GET', '/api/projects/' . $project->getId());
        
        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        
        $response = $this->getJsonResponse();
        $this->assertEquals('Mon Super Projet', $response['name']);
        $this->assertEquals('Description détaillée du projet', $response['description']);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('createdAt', $response);
    }

    /**
     * Test 3 : POST /api/projects
     * Doit créer un nouveau projet
     */
    public function testCreateProjectSuccess(): void
    {
        // Arrange
        $user = $this->createUser('project_creator@test.com');
        
        // Act
        $this->loginUser($user);
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'Nouveau Projet Test',
            'description' => 'Description du nouveau projet'
        ]);
        
        // Assert
        $this->assertResponseStatusCodeSame(201);
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertEquals('Projet créé avec succès', $response['message']);
        $this->assertEquals('Nouveau Projet Test', $response['project']['name']);
        $this->assertEquals('Description du nouveau projet', $response['project']['description']);
        $this->assertArrayHasKey('id', $response['project']);
    }

    /**
     * Test 4 : PUT /api/projects/{id}
     * Doit modifier un projet existant
     */
    public function testUpdateProjectSuccess(): void
    {
        // Arrange
        $user = $this->createUser('project_updater@test.com');
        
        $project = new Project();
        $project->setName('Ancien Nom');
        $project->setDescription('Ancienne description');
        $project->setOwner($user);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // Act
        $this->loginUser($user);
        $this->jsonRequest('PUT', '/api/projects/' . $project->getId(), [
            'name' => 'Nouveau Nom Modifié',
            'description' => 'Description modifiée'
        ]);
        
        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        
        $response = $this->getJsonResponse();
        $this->assertTrue($response['success']);
        $this->assertEquals('Projet modifié avec succès', $response['message']);
        $this->assertEquals('Nouveau Nom Modifié', $response['project']['name']);
        $this->assertEquals('Description modifiée', $response['project']['description']);
    }
}