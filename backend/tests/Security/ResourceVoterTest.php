<?php

namespace App\Tests\Security;

use App\Document\Snippet;
use App\Entity\Competence;
use App\Entity\Project;
use App\Entity\Task;
use App\Tests\ApiTestCase;

/**
 * Tests du ResourceVoter (logique d'autorisation ownership)
 * Vérifie que seul le propriétaire d'une ressource peut y accéder
 * 
 * Teste les 3 permissions : VIEW, EDIT, DELETE
 * Teste les 4 types de ressources : Project, Task, Competence, Snippet
 */
class ResourceVoterTest extends ApiTestCase
{
    /**
     * TEST 1 : Owner peut voir son Project (VIEW)
     */
    public function testOwnerCanViewOwnProject(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($owner);
        $this->client->request('GET', '/api/projects/' . $project->getId());
        
        // ASSERT
        $this->assertResponseIsSuccessful();
    }

    /**
     * TEST 2 : Non-owner ne peut PAS voir le Project d'un autre (VIEW refusé)
     */
    public function testNonOwnerCannotViewOthersProject(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $project = new Project();
        $project->setName('Projet du owner');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->client->request('GET', '/api/projects/' . $project->getId());
        
        // ASSERT : ResourceVoter refuse l'accès
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 3 : Owner peut modifier son Project (EDIT)
     */
    public function testOwnerCanEditOwnProject(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        
        $project = new Project();
        $project->setName('Ancien nom');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($owner);
        $this->apiRequest('PUT', '/api/projects/' . $project->getId(), [
            'name' => 'Nouveau nom'
        ]);
        
        // ASSERT
        $this->assertResponseIsSuccessful();
    }

    /**
     * TEST 4 : Non-owner ne peut PAS modifier le Project d'un autre (EDIT refusé)
     */
    public function testNonOwnerCannotEditOthersProject(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $project = new Project();
        $project->setName('Projet du owner');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->apiRequest('PUT', '/api/projects/' . $project->getId(), [
            'name' => 'Tentative piratage'
        ]);
        
        // ASSERT : ResourceVoter refuse
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 5 : Owner peut supprimer son Project (DELETE)
     */
    public function testOwnerCanDeleteOwnProject(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        
        $project = new Project();
        $project->setName('Projet à supprimer');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($owner);
        $this->apiRequest('DELETE', '/api/projects/' . $project->getId());
        
        // ASSERT
        $this->assertResponseIsSuccessful();
    }

    /**
     * TEST 6 : Non-owner ne peut PAS supprimer le Project d'un autre (DELETE refusé)
     */
    public function testNonOwnerCannotDeleteOthersProject(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $project = new Project();
        $project->setName('Projet du owner');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->apiRequest('DELETE', '/api/projects/' . $project->getId());
        
        // ASSERT : ResourceVoter refuse
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 7 : Task ownership INDIRECT via Project
     * Vérifie : $subject->getProject()->getOwner() === $user
     */
    public function testTaskOwnershipViaProject(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $project = new Project();
        $project->setName('Projet du owner');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        
        $task = new Task();
        $task->setTitle('Tâche du owner');
        $task->setStatus('todo');
        $task->setPosition(1);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        $this->entityManager->persist($task);
        
        $this->entityManager->flush();
        
        // ACT : Autre user tente d'accéder
        $this->loginUser($otherUser);
        $this->client->request('GET', '/api/tasks/' . $task->getId());
        
        // ASSERT : ResourceVoter refuse car Task->Project->Owner != otherUser
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 8 : Owner peut accéder à ses Tasks via ownership indirect
     */
    public function testOwnerCanAccessOwnTaskViaProject(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        
        $project = new Project();
        $project->setName('Mon Projet');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        
        $task = new Task();
        $task->setTitle('Ma Tâche');
        $task->setStatus('todo');
        $task->setPosition(1);
        $task->setCreatedAt(new \DateTime());
        $task->setProject($project);
        $this->entityManager->persist($task);
        
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($owner);
        $this->client->request('GET', '/api/tasks/' . $task->getId());
        
        // ASSERT : Autorisé
        $this->assertResponseIsSuccessful();
    }

    /**
     * TEST 9 : Competence ownership direct via getOwner()
     */
    public function testCompetenceOwnership(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $competence = new Competence();
        $competence->setName('PHP');
        $competence->setOwner($owner);
        $this->entityManager->persist($competence);
        $this->entityManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->client->request('GET', '/api/competences/' . $competence->getId());
        
        // ASSERT : ResourceVoter refuse
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 10 : Snippet MongoDB ownership via getUserId() string comparison
     * Vérifie : $subject->getUserId() === (string) $user->getId()
     */
    public function testSnippetMongoDBOwnershipWithStringComparison(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        
        $snippet = new Snippet();
        $snippet->setTitle('Mon Snippet');
        $snippet->setLanguage('php');
        $snippet->setCode('<?php echo "test";');
        $snippet->setUserId((string) $owner->getId()); // String conversion
        $this->documentManager->persist($snippet);
        $this->documentManager->flush();
        
        // ACT
        $this->loginUser($otherUser);
        $this->client->request('GET', '/api/snippets/' . $snippet->getId());
        
        // ASSERT : ResourceVoter refuse car userId !== otherUser->getId()
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * TEST 11 : Owner peut accéder à son Snippet MongoDB
     */
    public function testOwnerCanAccessOwnSnippet(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        
        $snippet = new Snippet();
        $snippet->setTitle('Mon Snippet');
        $snippet->setLanguage('javascript');
        $snippet->setCode('console.log("test");');
        $snippet->setUserId((string) $owner->getId());
        $this->documentManager->persist($snippet);
        $this->documentManager->flush();
        
        // ACT
        $this->loginUser($owner);
        $this->client->request('GET', '/api/snippets/' . $snippet->getId());
        
        // ASSERT : Autorisé
        $this->assertResponseIsSuccessful();
    }

    /**
     * TEST 12 : Utilisateur non connecté ne peut PAS accéder
     * Note: Le firewall Symfony redirige vers /auth au lieu de retourner 401
     */
    public function testAnonymousUserCannotAccessResources(): void
    {
        // ARRANGE
        $owner = $this->createUser('owner@test.com');
        
        $project = new Project();
        $project->setName('Projet');
        $project->setOwner($owner);
        $project->setCreatedAt(new \DateTime());
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        // ACT : Pas de login
        $this->client->request('GET', '/api/projects/' . $project->getId());
        
        // ASSERT : Le firewall redirige vers /auth (302) ou retourne 401
        // Les deux sont acceptables pour la sécurité
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertContains(
            $statusCode,
            [302, 401],
            'Un utilisateur non connecté doit être redirigé (302) ou refusé (401)'
        );
        
        // Si redirection, vérifier que c'est bien vers /auth
        if ($statusCode === 302) {
            $this->assertEquals(
                '/auth',
                $this->client->getResponse()->headers->get('Location')
            );
        }
    }
}