# MY-ANKODE - Backend Documentation

> Documentation technique du backend Symfony 7 - Architecture API REST + Twig Templates

---

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Stack technique](#stack-technique)
- [Architecture hybride](#architecture-hybride)
- [Entities PostgreSQL](#entities-postgresql)
- [Documents MongoDB](#documents-mongodb)
- [API REST Endpoints](#api-rest-endpoints)
- [Commandes console](#commandes-console)
- [Installation](#installation)
- [Tests](#tests)

---

## 🎯 Vue d'ensemble

Le backend MY-ANKODE est une **API REST Symfony 7** avec des **templates Twig** pour le frontend MVP certification. Il utilise une **architecture hybride PostgreSQL + MongoDB** pour optimiser les performances selon les types de données.

**Caractéristiques principales :**
- ✅ API REST complète (JSON)
- ✅ Authentification Symfony Security (bcrypt)
- ✅ Architecture hybride SQL/NoSQL
- ✅ Docker dev + prod ready
- ✅ Tests unitaires PHPUnit
- ✅ Templates Twig + Bootstrap 5

---

## 🛠️ Stack technique

### Backend
- **Framework** : Symfony 7.2 (PHP 8.3+)
- **Bases de données** :
  - PostgreSQL 16 (relationnel)
  - MongoDB 6 (documentaire)
- **ORM/ODM** :
  - Doctrine ORM (PostgreSQL)
  - Doctrine MongoDB ODM
- **Authentification** : Symfony Security + bcrypt
- **Templating** : Twig 3.x

### DevOps
- **Environnement dev** : Docker + PHP built-in server (port 8000)
- **Environnement prod** : Docker + Nginx + PHP-FPM (port 80)
- **Tests** : PHPUnit 11.x

---

## 🗄️ Architecture hybride

### PostgreSQL (Relationnel)
**Entités avec relations strictes nécessitant intégrité référentielle**

```
User (id, email, password, username, roles, created_at)
  ↓ OneToMany
Projects (id, user_id, name, description, created_at)
  ↓ OneToMany
Tasks (id, project_id, title, description, status, position, created_at)

User (id, ...)
  ↓ OneToMany
Competences (id, user_id, name, level, notes, created_at)
```

**Avantages :**
- Relations CASCADE (supprimer user → supprimer projects → supprimer tasks)
- Transactions ACID
- Intégrité référentielle garantie
- Requêtes JOIN optimisées

---

### MongoDB (Documentaire)
**Documents flexibles sans relations complexes**

```json
// Collection: snippets
{
  "_id": ObjectId("..."),
  "userId": "1",
  "title": "Fonction utile",
  "language": "php",
  "code": "function example() { ... }",
  "description": "Description optionnelle",
  "tags": ["php", "function", "utils"],
  "createdAt": ISODate("2025-01-06T...")
}

// Collection: articles
{
  "_id": ObjectId("..."),
  "title": "Nouveautés PHP 8.4",
  "url": "https://...",
  "source": "Dev.to",
  "publishedAt": ISODate("2025-01-05T..."),
  "createdAt": ISODate("2025-01-06T...")
}
```

**Avantages :**
- Schéma flexible (code multi-langages, RSS variables)
- Arrays natifs (tags sans table de liaison)
- Performance lecture sur gros volumes
- Pas de foreign keys (référence userId en string)

---

## 📦 Entities PostgreSQL

### 1. User
**Fichier :** `src/Entity/User.php`

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int (PK) | Identifiant unique |
| `email` | string (unique) | Email de connexion |
| `password` | string | Mot de passe hashé (bcrypt) |
| `username` | string | Nom d'utilisateur |
| `roles` | json | Rôles utilisateur (ROLE_USER par défaut) |
| `createdAt` | DateTime | Date de création |

**Relations :**
- OneToMany → `projects` (cascade persist, remove)
- OneToMany → `competences` (cascade persist, remove)

**Repository :** `src/Repository/UserRepository.php`

---

### 2. Project
**Fichier :** `src/Entity/Project.php`

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int (PK) | Identifiant unique |
| `user` | User (FK) | Propriétaire du projet |
| `name` | string | Nom du projet |
| `description` | text (nullable) | Description détaillée |
| `createdAt` | DateTime | Date de création |

**Relations :**
- ManyToOne → `user`
- OneToMany → `tasks` (cascade persist, remove)

**Repository :** `src/Repository/ProjectRepository.php`

---

### 3. Task
**Fichier :** `src/Entity/Task.php`

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int (PK) | Identifiant unique |
| `project` | Project (FK) | Projet parent |
| `title` | string | Titre de la tâche |
| `description` | text (nullable) | Description détaillée |
| `status` | string | Statut Kanban (todo, in_progress, done) |
| `position` | int | Ordre d'affichage dans la colonne |
| `createdAt` | DateTime | Date de création |

**Relations :**
- ManyToOne → `project`

**Repository :** `src/Repository/TaskRepository.php`

**Méthodes personnalisées :**
```php
findByProject(Project $project): array
findByProjectAndStatus(Project $project, string $status): array
```

---

### 4. Competence
**Fichier :** `src/Entity/Competence.php`

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int (PK) | Identifiant unique |
| `user` | User (FK) | Propriétaire de la compétence |
| `name` | string | Nom de la compétence (ex: "PHP", "Symfony") |
| `level` | int | Niveau d'auto-évaluation (1-5) |
| `notes` | text (nullable) | Notes personnelles |
| `createdAt` | DateTime | Date de création |

**Relations :**
- ManyToOne → `user`

**Repository :** `src/Repository/CompetenceRepository.php`

**Méthodes personnalisées :**
```php
findByUser(User $user): array
findByUserAndLevel(User $user, int $minLevel): array
```

---

## 📄 Documents MongoDB

### 1. Snippet
**Fichier :** `src/Document/Snippet.php`

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | ObjectId (PK) | Identifiant MongoDB |
| `userId` | string | Référence User (string, pas de FK) |
| `title` | string | Titre du snippet |
| `language` | string | Langage (php, js, html, css, sql, other) |
| `code` | string | Code source |
| `description` | string (nullable) | Description optionnelle |
| `tags` | array | Tags (array natif MongoDB) |
| `createdAt` | DateTime | Date de création |

**Repository :** `src/Repository/SnippetRepository.php`

**Méthodes personnalisées :**
```php
findByUserId(string $userId): array
findByLanguage(string $language): array
```

**Avantages MongoDB :**
- Stockage flexible du code (tous langages)
- Tags en array natif (pas de table snippet_tags)
- Recherche full-text possible

---

### 2. Article
**Fichier :** `src/Document/Article.php`

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | ObjectId (PK) | Identifiant MongoDB |
| `title` | string | Titre de l'article |
| `url` | string | URL de l'article |
| `source` | string | Source (Dev.to, Medium, Korben, etc.) |
| `publishedAt` | DateTime | Date de publication originale |
| `createdAt` | DateTime | Date d'import dans MY-ANKODE |

**Repository :** `src/Repository/ArticleRepository.php`

**Méthodes personnalisées :**
```php
findLatest(int $limit = 20): array
findBySource(string $source): array
```

**Avantages MongoDB :**
- Schéma flexible (RSS variables selon sources)
- Performance lecture (nombreux articles)
- Métadonnées extensibles (ajouter champs sans migration)

---

## 🌐 API REST Endpoints

### Authentification

#### Inscription
```http
POST /register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "username": "JohnDoe"
}

Response: 201 Created
{
  "message": "User registered successfully",
  "userId": 1
}
```

#### Connexion
```http
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response: 200 OK
Set-Cookie: PHPSESSID=...

{
  "message": "Login successful",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "username": "JohnDoe"
  }
}
```

#### Déconnexion
```http
GET /logout

Response: 302 Found
Location: /auth
```

---

### Projects

**Controller :** `src/Controller/ProjectController.php`

#### Lister les projets de l'utilisateur connecté
```http
GET /api/projects
Authorization: Cookie (session Symfony)

Response: 200 OK
[
  {
    "id": 1,
    "name": "Mon projet",
    "description": "Description",
    "createdAt": "2025-01-06T10:00:00+00:00",
    "tasksCount": 5
  }
]
```

#### Créer un projet
```http
POST /api/projects
Content-Type: application/json

{
  "name": "Nouveau projet",
  "description": "Description optionnelle"
}

Response: 201 Created
{
  "id": 2,
  "name": "Nouveau projet",
  "description": "Description optionnelle",
  "createdAt": "2025-01-06T10:30:00+00:00"
}
```

#### Modifier un projet
```http
PUT /api/projects/{id}
Content-Type: application/json

{
  "name": "Projet renommé",
  "description": "Nouvelle description"
}

Response: 200 OK
```

#### Supprimer un projet
```http
DELETE /api/projects/{id}

Response: 204 No Content
```

**Sécurité :** Vérification ownership (projet appartient à l'utilisateur connecté)

---

### Tasks

**Controller :** `src/Controller/TaskController.php`

#### Lister les tâches d'un projet
```http
GET /api/projects/{projectId}/tasks

Response: 200 OK
{
  "todo": [
    {
      "id": 1,
      "title": "Tâche à faire",
      "description": "Description",
      "status": "todo",
      "position": 0,
      "createdAt": "2025-01-06T10:00:00+00:00"
    }
  ],
  "in_progress": [...],
  "done": [...]
}
```

#### Créer une tâche
```http
POST /api/tasks
Content-Type: application/json

{
  "title": "Nouvelle tâche",
  "description": "Description optionnelle",
  "projectId": 1,
  "status": "todo"
}

Response: 201 Created
```

#### Changer le statut d'une tâche
```http
PATCH /api/tasks/{id}/status
Content-Type: application/json

{
  "status": "in_progress"
}

Response: 200 OK
```

#### Supprimer une tâche
```http
DELETE /api/tasks/{id}

Response: 204 No Content
```

**Sécurité :** Vérification ownership (tâche appartient à un projet de l'utilisateur)

---

### Snippets (MongoDB)

**Controller :** `src/Controller/SnippetController.php`

#### Lister les snippets de l'utilisateur
```http
GET /api/snippets

Response: 200 OK
[
  {
    "id": "677c1234567890abcdef1234",
    "title": "Fonction utile",
    "language": "php",
    "code": "function example() { return true; }",
    "description": "Description",
    "tags": ["php", "function"],
    "createdAt": "2025-01-06T10:00:00+00:00"
  }
]
```

#### Créer un snippet
```http
POST /api/snippets
Content-Type: application/json

{
  "title": "Mon snippet",
  "language": "js",
  "code": "console.log('Hello');",
  "description": "Description optionnelle",
  "tags": ["javascript", "console"]
}

Response: 201 Created
```

#### Modifier un snippet
```http
PUT /api/snippets/{id}
Content-Type: application/json

{
  "title": "Titre modifié",
  "code": "console.log('Modified');"
}

Response: 200 OK
```

#### Supprimer un snippet
```http
DELETE /api/snippets/{id}

Response: 204 No Content
```

**Langages supportés :** `php`, `js`, `html`, `css`, `sql`, `other`

---

### Competences

**Controller :** `src/Controller/CompetenceController.php`

#### Lister les compétences de l'utilisateur
```http
GET /api/competences

Response: 200 OK
[
  {
    "id": 1,
    "name": "Symfony",
    "level": 4,
    "notes": "Maîtrise API REST",
    "createdAt": "2025-01-06T10:00:00+00:00"
  }
]
```

#### Créer une compétence
```http
POST /api/competences
Content-Type: application/json

{
  "name": "Angular",
  "level": 3,
  "notes": "En cours d'apprentissage"
}

Response: 201 Created
```

#### Modifier une compétence
```http
PUT /api/competences/{id}
Content-Type: application/json

{
  "name": "Angular",
  "level": 4,
  "notes": "Niveau confirmé"
}

Response: 200 OK
```

#### Supprimer une compétence
```http
DELETE /api/competences/{id}

Response: 204 No Content
```

**Validation :** `level` doit être entre 1 et 5

---

## 🖥️ Commandes console

### Tests MongoDB

#### Tester la connexion MongoDB
```bash
php bin/console app:test-mongo
```

**Résultat attendu :**
```
✅ Connexion MongoDB réussie
🗄️ Base : my_ankode
📂 Collections : snippets, articles
```

---

#### Insérer des données de test MongoDB
```bash
php bin/console app:test-mongo-insert
```

**Résultat attendu :**
```
✅ 1 Snippet créé
✅ 1 Article créé
```

---

### Veille RSS

#### Importer un flux RSS
```bash
php bin/console app:fetch-rss <url> <source_name>
```

**Exemples :**
```bash
# Flux français
php bin/console app:fetch-rss https://korben.info/feed "Korben"

# Flux anglais
php bin/console app:fetch-rss https://dev.to/feed "Dev.to"
php bin/console app:fetch-rss https://medium.com/feed/tag/javascript "Medium JS"
```

**Comportement :**
- Parse le flux RSS XML
- Crée un document `Article` par entrée
- Évite les doublons (vérification URL)
- Stocke dans MongoDB

---

## 🚀 Installation

### Avec Docker (recommandé)

```bash
# 1. Lancer Docker
docker-compose up -d

# 2. Entrer dans le conteneur backend
docker-compose exec backend sh

# 3. Installer les dépendances
composer install

# 4. Créer la base PostgreSQL
php bin/console doctrine:database:create

# 5. Exécuter les migrations
php bin/console doctrine:migrations:migrate

# 6. (Optionnel) Charger des fixtures
php bin/console doctrine:fixtures:load

# 7. Vérifier MongoDB
php bin/console app:test-mongo

# 8. Importer des articles RSS (optionnel)
php bin/console app:fetch-rss https://korben.info/feed "Korben"

exit
```

### Accéder à l'application
- **Frontend** : http://localhost:8000/auth
- **Dashboard** : http://localhost:8000/dashboard (après connexion)

---

### Sans Docker (manuel)

```bash
cd backend

# 1. Installer les dépendances
composer install

# 2. Configurer .env.local
cp .env .env.local
# Éditer .env.local avec vos paramètres PostgreSQL/MongoDB

# 3. Créer la base PostgreSQL
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 4. Lancer le serveur Symfony
symfony serve
# OU
php -S localhost:8000 -t public
```

**Configuration `.env.local` :**
```env
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/my_ankode?serverVersion=16&charset=utf8"
MONGODB_URL="mongodb://127.0.0.1:27017"
MONGODB_DB="my_ankode"
APP_ENV=dev
APP_DEBUG=1
```

---

## 🧪 Tests

### Tests unitaires PHPUnit

```bash
cd backend

# Lancer tous les tests
php bin/phpunit

# Tester une classe spécifique
php bin/phpunit tests/Entity/UserTest.php

# Tests avec couverture de code
php bin/phpunit --coverage-html coverage/
```

---

### Tests manuels avec Postman

**Collection Postman disponible** : `/docs/postman/MY-ANKODE.postman_collection.json`

**Workflow de test :**
1. Inscription → `POST /register`
2. Connexion → `POST /login` (récupérer cookie session)
3. Créer projet → `POST /api/projects`
4. Créer tâche → `POST /api/tasks`
5. Créer snippet → `POST /api/snippets`
6. Créer compétence → `POST /api/competences`

---

## 🔧 Configuration Symfony

### Doctrine (PostgreSQL)
**Fichier :** `config/packages/doctrine.yaml`

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        server_version: '16'
    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App
```

---

### Doctrine MongoDB
**Fichier :** `config/packages/doctrine_mongodb.yaml`

```yaml
doctrine_mongodb:
    connections:
        default:
            server: '%env(resolve:MONGODB_URL)%'
    default_database: '%env(resolve:MONGODB_DB)%'
    document_managers:
        default:
            auto_mapping: true
            mappings:
                App:
                    type: attribute
                    is_bundle: false
                    dir: '%kernel.project_dir%/src/Document'
                    prefix: 'App\Document'
                    alias: App
```

---

### Security
**Fichier :** `config/packages/security.yaml`

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_auth
                check_path: app_auth
            logout:
                path: app_logout
                target: app_auth

    access_control:
        - { path: ^/auth, roles: PUBLIC_ACCESS }
        - { path: ^/register, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: ROLE_USER }
        - { path: ^/dashboard, roles: ROLE_USER }
```

## 🧪 Tests

### Vue d'ensemble

**Total : 34 tests automatisés PHPUnit**
- 19 tests unitaires (validation entités)
- 15 tests fonctionnels (API REST + MongoDB)
```
tests/
├── ApiTestCase.php              # Helper pour tests API (PostgreSQL + MongoDB)
├── Entity/                      # Tests unitaires (19 tests)
│   ├── UserTest.php
│   ├── ProjectTest.php
│   ├── TaskTest.php
│   └── CompetenceTest.php
├── Controller/                  # Tests fonctionnels API REST (11 tests)
│   ├── TaskControllerTest.php
│   └── ProjectControllerTest.php
└── Document/                    # Tests fonctionnels MongoDB (4 tests)
    └── ArticleMongoTest.php
```

---

### Lancer les tests
```bash
# Tous les tests
docker-compose exec backend php bin/phpunit

# Tests entités uniquement
docker-compose exec backend php bin/phpunit tests/Entity/

# Tests controllers uniquement
docker-compose exec backend php bin/phpunit tests/Controller/

# Tests MongoDB uniquement
docker-compose exec backend php bin/phpunit tests/Document/

# Format lisible
docker-compose exec backend php bin/phpunit --testdox
```

**Résultat attendu :**
```
OK (34 tests, 119 assertions)
Time: ~45s
```

---

### Configuration

**Base de test :** PostgreSQL séparée (`.env.test`)

**Initialisation base de test :**
```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
```

---

### Couverture

**Tests unitaires (19 tests) :**
- Validation contraintes Doctrine
- Relations entités (OneToMany, ManyToOne)
- Valeurs par défaut (createdAt, roles, status)

**Tests fonctionnels API REST (11 tests) :**
- TaskController : 7 tests (CRUD complet + ownership)
- ProjectController : 4 tests (GET, POST, PUT)
- Codes HTTP : 200, 201, 403, 404

**Tests fonctionnels MongoDB (4 tests) :**
- Création d'articles RSS dans MongoDB
- Lecture d'articles par ID
- Filtrage d'articles par utilisateur (isolation)
- Marquage articles lu/non-lu

**Code coverage estimé :** ~75% sur entités/controllers/documents critiques

---

### Tests manuels

**Collection Postman :** `/docs/postman/MY-ANKODE.postman_collection.json`

**Workflow :**
1. POST /register → Inscription
2. POST /login → Connexion (récupérer cookie)
3. POST /api/projects → Créer projet
4. POST /api/tasks → Créer tâche
5. GET /api/projects/{id}/tasks → Lister tâches

---

## 🎯 Résumé des choix techniques

### Pourquoi Symfony 7 ?
✅ Framework mature et professionnel  
✅ Doctrine ORM/ODM intégrés  
✅ Système de sécurité robuste  
✅ Twig natif pour templates  
✅ Excellente documentation

### Pourquoi PostgreSQL + MongoDB ?
✅ **PostgreSQL** : Relations strictes (User → Projects → Tasks)  
✅ **MongoDB** : Flexibilité (Snippets multi-langages, Articles RSS variables)  
✅ Meilleur des deux mondes selon les besoins

### Pourquoi Docker ?
✅ Environnements reproductibles (dev = prod)  
✅ Pas de conflits de versions PHP/PostgreSQL/MongoDB  
✅ Déploiement simplifié  
✅ Isolation complète

---

## 📚 Documentation complémentaire

- **[ARCHITECTURE.md](../ARCHITECTURE.md)** - Architecture 3-tiers détaillée
- **[DECISIONS.md](../DECISIONS.md)** - Justifications techniques
- **[README.md principal](../README.md)** - Vue d'ensemble du projet

---

## 👨‍💻 Auteur

**Anthony CATAN-CAVERY** - Développeur Web et Web Mobile en formation  
🔗 [LinkedIn](https://www.linkedin.com/in/anthonycatancavery)  
🎓 Certification DWWM - Février 2026

---

**📝 Note :** Cette documentation est maintenue à jour à chaque sprint. Dernière mise à jour : 06 janvier 2026