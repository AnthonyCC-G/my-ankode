# Tests

Documentation de la stratégie de tests et de la couverture de MY-ANKODE.

[← Retour au README principal](../README.md)

---

## Table des matières

- [Philosophie](#philosophie)
- [Vue d'ensemble](#vue-densemble)
- [Configuration PHPUnit](#configuration-phpunit)
- [Types de tests](#types-de-tests)
- [Exemples de tests](#exemples-de-tests)
- [Lancer les tests](#lancer-les-tests)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Philosophie

MY-ANKODE adopte une approche **qualité > quantité** pour les tests.

### Principes directeurs

**Objectif** : Démontrer la **maîtrise des tests**, pas la couverture exhaustive.

- ✅ **Tests ciblés** : Focus sur les fonctionnalités critiques
- ✅ **Tests lisibles** : Nommage clair, structure AAA (Arrange-Act-Assert)
- ✅ **Tests maintenables** : Éviter la duplication, utiliser des helpers
- ✅ **Tests significatifs** : Chaque test prouve une compétence

**135 tests** couvrent stratégiquement :
- Entities (validation, relations)
- Controllers (API REST, CRUD)
- Security (authentification, ownership, CSRF, injection)
- Documents MongoDB

### Couverture référentiel DWWM

Les tests couvrent **CP6** : *Développer les composants d'accès aux données*

- Jeux d'essai fonctionnels ✅
- Tests unitaires ✅
- Tests de sécurité ✅
- Résolution structurée de problèmes ✅

---

## Vue d'ensemble

### Répartition des tests

```
Total : 135 tests

tests/
├── Entity/               # Tests unitaires entités
│   ├── UserTest.php                    (8 tests)
│   ├── ProjectTest.php                 (8 tests)
│   ├── TaskTest.php                    (8 tests)
│   └── CompetenceTest.php              (8 tests)
│
├── Controller/           # Tests fonctionnels API
│   ├── ProjectControllerTest.php       (15 tests)
│   ├── TaskControllerTest.php          (18 tests)
│   ├── VeilleControllerTest.php        (12 tests)
│   ├── SnippetControllerTest.php       (15 tests)
│   └── CompetenceControllerTest.php    (12 tests)
│
├── Security/             # Tests sécurité
│   ├── AuthenticationTest.php          (5 tests)
│   ├── OwnershipTest.php               (10 tests)
│   ├── ValidationTest.php              (6 tests)
│   ├── InputSanitizationTest.php       (5 tests)
│   ├── PasswordSecurityTest.php        (5 tests)
│   ├── ResourceVoterTest.php           (8 tests)
│   └── SecurityHeadersTest.php         (3 tests)
│
└── Document/             # Tests MongoDB
    ├── ArticleMongoTest.php            (6 tests)
    └── SnippetTest.php                 (3 tests)
```

### Métriques

- **135 tests** au total
- **Temps d'exécution** : ~15 secondes
- **Environnement** : Base de données test dédiée
- **Isolation** : Chaque test recrée un contexte propre

---

## Configuration PHPUnit

### phpunit.xml.dist

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    
    <testsuites>
        <testsuite name="Entity">
            <directory>tests/Entity</directory>
        </testsuite>
        <testsuite name="Controller">
            <directory>tests/Controller</directory>
        </testsuite>
        <testsuite name="Document">
            <directory>tests/Document</directory>
        </testsuite>
        <testsuite name="Security">
            <directory>tests/Security</directory>
        </testsuite>
    </testsuites>

    <php>
        <server name="APP_ENV" value="test" force="true" />
    </php>

    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/DataFixtures</directory>
            <file>src/Kernel.php</file>
        </exclude>
    </source>
</phpunit>
```

### Environnement de test

```bash
# .env.test
APP_ENV=test
DATABASE_URL="postgresql://test:test@postgres:5432/myankode_test"
MONGODB_URL="mongodb://mongo:27017"
MONGODB_DB="myankode_test"
```

**Caractéristiques** :
- Base PostgreSQL séparée (`myankode_test`)
- Base MongoDB séparée
- Fixtures chargées automatiquement
- Cache désactivé

---

## Types de tests

### 1. Tests unitaires (Entities)

**Objectif** : Vérifier le comportement des entités isolément.

**Exemple : TaskTest**

```php
public function testTaskGettersAndSetters(): void
{
    // ARRANGE
    $task = new Task();
    $now = new \DateTime();
    
    // ACT
    $task->setTitle('Ma tâche test');
    $task->setStatus('todo');
    $task->setCreatedAt($now);

    // ASSERT
    $this->assertEquals('Ma tâche test', $task->getTitle());
    $this->assertEquals('todo', $task->getStatus());
    $this->assertEquals($now, $task->getCreatedAt());
}
```

**Ce qu'on teste** :
- Getters/Setters fonctionnent
- Contraintes de validation (longueur, types)
- Relations entre entités (ManyToOne, OneToMany)
- Valeurs par défaut

---

### 2. Tests fonctionnels (Controllers)

**Objectif** : Tester les endpoints API avec vraies requêtes HTTP.

**Exemple : ProjectControllerTest**

```php
public function testCreateProjectSuccess(): void
{
    // ARRANGE
    $user = $this->createUser('test@example.com');
    
    // ACT
    $this->loginUser($user);
    $this->apiRequest('POST', '/api/projects', [
        'name' => 'Mon nouveau projet',
        'description' => 'Description du projet'
    ]);
    
    // ASSERT
    $this->assertResponseStatusCodeSame(201);
    
    $response = $this->getJsonResponse();
    $this->assertEquals('Mon nouveau projet', $response['project']['name']);
    
    // Vérifier en base
    $project = $this->entityManager->getRepository(Project::class)
        ->find($response['project']['id']);
    $this->assertNotNull($project);
    $this->assertEquals($user, $project->getOwner());
}
```

**Ce qu'on teste** :
- Routes API (GET, POST, PUT, DELETE)
- Codes de statut HTTP (200, 201, 400, 403, 404)
- Format des réponses JSON
- Persistance en base de données
- Ownership des ressources

---

### 3. Tests de sécurité

#### Authentification

```php
public function testGetProjectsWithoutLogin(): void
{
    // ACT : Requête sans login
    $this->client->request('GET', '/api/projects');
    
    // ASSERT : 401 Unauthorized
    $statusCode = $this->client->getResponse()->getStatusCode();
    $this->assertTrue($statusCode === 401 || $statusCode === 302);
}
```

#### Ownership (ResourceVoter)

```php
public function testUserCannotEditOthersProject(): void
{
    // ARRANGE
    $alice = $this->createUser('alice@test.com');
    $bob = $this->createUser('bob@test.com');
    
    $bobProject = new Project();
    $bobProject->setName('Projet de Bob');
    $bobProject->setOwner($bob);
    $this->entityManager->persist($bobProject);
    $this->entityManager->flush();
    
    // ACT : Alice tente de modifier le projet de Bob
    $this->loginUser($alice);
    $this->apiRequest('PUT', "/api/projects/{$bobProject->getId()}", [
        'name' => 'Projet hacké'
    ]);
    
    // ASSERT : 403 Forbidden
    $this->assertResponseStatusCodeSame(403);
}
```

#### Injection SQL

```php
public function testSqlInjectionIsBlocked(): void
{
    // ARRANGE
    $user = $this->createUser('test@example.com');
    
    // ACT : Tentative injection SQL
    $maliciousName = "Mon Projet'; DROP TABLE project; --";
    
    $this->loginUser($user);
    $this->apiRequest('POST', '/api/projects', [
        'name' => $maliciousName,
        'description' => 'Test injection'
    ]);
    
    // ASSERT : Projet créé avec nom échappé, table existe toujours
    $this->assertResponseStatusCodeSame(201);
    
    $projects = $this->entityManager->getRepository(Project::class)->findAll();
    $this->assertNotEmpty($projects, 'Table project doit exister');
}
```

#### Validation

```php
public function testCreateTaskWithoutTitle(): void
{
    // ARRANGE
    $user = $this->createUser('test@example.com');
    $project = $this->createProject($user);
    
    // ACT : Task sans title (obligatoire)
    $this->loginUser($user);
    $this->apiRequest('POST', "/api/projects/{$project->getId()}/tasks", [
        'status' => 'todo'
        // 'title' manquant
    ]);
    
    // ASSERT : 400 Bad Request
    $this->assertResponseStatusCodeSame(400);
}
```

---

### 4. Tests MongoDB (Documents)

```php
public function testArticleMarkAsRead(): void
{
    // ARRANGE
    $article = new Article();
    $article->setTitle('Article test');
    $article->setUrl('https://example.com');
    $this->dm->persist($article);
    $this->dm->flush();
    
    $userId = 'user123';
    
    // ACT
    $article->markAsReadByUser($userId);
    $this->dm->flush();
    
    // ASSERT
    $this->assertTrue($article->isReadByUser($userId));
    $this->assertContains($userId, $article->getReadBy());
}
```

---

## Exemples de tests

### Pattern AAA (Arrange-Act-Assert)

Tous les tests suivent cette structure :

```php
public function testExemple(): void
{
    // ARRANGE : Préparation (données, contexte)
    $user = $this->createUser('test@example.com');
    $project = new Project();
    $project->setName('Test');
    $project->setOwner($user);
    
    // ACT : Action à tester
    $this->loginUser($user);
    $this->apiRequest('GET', "/api/projects/{$project->getId()}");
    
    // ASSERT : Vérifications
    $this->assertResponseIsSuccessful();
    $response = $this->getJsonResponse();
    $this->assertEquals('Test', $response['name']);
}
```

### ApiTestCase (classe de base)

MY-ANKODE utilise une classe `ApiTestCase` qui fournit des helpers :

```php
abstract class ApiTestCase extends WebTestCase
{
    // Helper : Créer un utilisateur
    protected function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername(explode('@', $email)[0]);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }
    
    // Helper : Login
    protected function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }
    
    // Helper : Requête API avec CSRF automatique
    protected function apiRequest(string $method, string $uri, array $data = []): void
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];
        
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $headers['HTTP_X-CSRF-Token'] = $this->getCsrfToken();
        }
        
        $this->client->request($method, $uri, [], [], $headers, json_encode($data));
    }
}
```

---

## Lancer les tests

### Tous les tests

```bash
# Avec Docker
docker compose exec php php bin/phpunit

# Localement (si configuré)
php bin/phpunit
```

**Output** :
```
PHPUnit 11.5.0

Entity (32 tests)     ................................  32 / 32 (100%)
Controller (72 tests) ........................................ ...  72 / 72 (100%)
Security (42 tests)   .......................................... 42 / 42 (100%)
Document (9 tests)    .........  9 / 9 (100%)

Time: 00:14.523, Memory: 128.00 MB

OK (135 tests, 450 assertions)
```

### Tests spécifiques

```bash
# Par suite
docker compose exec php php bin/phpunit --testsuite=Entity
docker compose exec php php bin/phpunit --testsuite=Controller
docker compose exec php php bin/phpunit --testsuite=Security

# Par fichier
docker compose exec php php bin/phpunit tests/Controller/ProjectControllerTest.php

# Par méthode
docker compose exec php php bin/phpunit --filter testCreateProjectSuccess
```

### Avec statistiques

```bash
# Verbeux avec détails
docker compose exec php php bin/phpunit --testdox

# Avec coverage (si xdebug activé)
docker compose exec php php bin/phpunit --coverage-html coverage
```

---

## Bonnes pratiques

### Nommage des tests

```php
// ✅ Bon : Décrit le comportement testé
public function testUserCannotDeleteOthersProject(): void

// ❌ Mauvais : Nom vague
public function testDelete(): void
```

### Isolation des tests

```php
protected function setUp(): void
{
    parent::setUp();
    // Chaque test part d'une BDD propre
}

protected function tearDown(): void
{
    // Nettoyage automatique
    parent::tearDown();
}
```

### Messages d'assertion clairs

```php
// ✅ Bon : Message explicite en cas d'échec
$this->assertEquals(
    'todo',
    $task->getStatus(),
    'Le statut par défaut doit être "todo"'
);

// ❌ Mauvais : Pas de message
$this->assertEquals('todo', $task->getStatus());
```

### Éviter la duplication

```php
// ✅ Bon : Helper réutilisable
private function createProjectWithTask(User $user): Project
{
    $project = new Project();
    $project->setName('Test Project');
    $project->setOwner($user);
    
    $task = new Task();
    $task->setTitle('Test Task');
    $task->setProject($project);
    
    $this->entityManager->persist($project);
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    
    return $project;
}
```

### Tests indépendants

```php
// ✅ Bon : Chaque test crée ses données
public function testA(): void
{
    $user = $this->createUser('test@example.com');
    // ...
}

public function testB(): void
{
    $user = $this->createUser('other@example.com'); // Nouveau user
    // ...
}

// ❌ Mauvais : Dépendance entre tests
private static $sharedUser; // État partagé = mauvais
```

---

## Scripts utilitaires

### Réinitialiser l'environnement de test

```bash
# Recharger fixtures + schéma
./scripts/reset-all-fixtures-test-docker.sh
```

### Vérifier les tests rapidement

```bash
# Script dédié
./scripts/check-tests-docker.sh
```

---

## Évolution des tests

### Actuellement

- **135 tests** couvrant les fonctionnalités critiques
- Focus sur **qualité** et **maintenabilité**


### Après certification

- **Tests end-to-end** (Symfony Panther)
- **Tests de performance** (charge, stress)
- **Mutation testing** (Infection)
- **CI/CD** : Tests automatiques sur GitHub Actions

---

[← Retour au README principal](../README.md)