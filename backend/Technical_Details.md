# MY-ANKODE - Documentation Technique Complète

> Documentation technique détaillée du backend Symfony 7 - Architecture API REST + Twig Templates

**Version :** 1.0.0  
**Date :** 09 janvier 2026  
**Auteur :** Anthony CATAN-CAVERY

---

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Stack technique](#stack-technique)
- [Architecture hybride](#architecture-hybride)
- [Entities PostgreSQL](#entities-postgresql)
- [Documents MongoDB](#documents-mongodb)
- [Controllers](#controllers)
  - [API REST Controllers](#api-rest-controllers)
  - [Page Controllers (Twig)](#page-controllers-twig)
- [API REST Endpoints](#api-rest-endpoints)
- [Commandes console](#commandes-console)
- [Configuration Symfony](#configuration-symfony)
- [Tests](#tests)
- [Sécurité](#sécurité)

---

## 🎯 Vue d'ensemble

Le backend MY-ANKODE est une **API REST Symfony 7** avec des **templates Twig** pour le frontend MVP certification. Il utilise une **architecture hybride PostgreSQL + MongoDB** pour optimiser les performances selon les types de données.

**Caractéristiques principales :**
- ✅ API REST complète (JSON)
- ✅ Pages HTML avec Twig + Bootstrap 5
- ✅ Authentification Symfony Security (bcrypt)
- ✅ Architecture hybride SQL/NoSQL
- ✅ Docker dev + prod ready
- ✅ Tests unitaires PHPUnit (47 tests)

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
- **Validation** : Symfony Validator

### Frontend MVP
- **Templates** : Twig 3.x
- **CSS Framework** : Bootstrap 5.3
- **JavaScript** : Vanilla JS (pas de framework)

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
Projects (id, owner_id, name, description, created_at)
  ↓ OneToMany
Tasks (id, project_id, title, description, status, position, created_at)

User (id, ...)
  ↓ OneToMany
Competences (id, owner_id, name, level, notes, projects_links, snippets_links, created_at)
```

**Avantages PostgreSQL :**
- Relations CASCADE (supprimer user → supprimer projects → supprimer tasks)
- Transactions ACID
- Intégrité référentielle garantie
- Requêtes JOIN optimisées
- Contraintes de validation au niveau BDD

---

### MongoDB (Documentaire)
**Documents flexibles sans relations complexes**

```json
// Collection: snippets
{
  "_id": ObjectId("677c1234567890abcdef1234"),
  "userId": 1,
  "title": "Fonction utile PHP",
  "language": "php",
  "code": "function slugify($text) {\n  return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));\n}",
  "description": "Transforme un texte en slug URL-friendly",
  "tags": ["php", "function", "string", "utils"],
  "createdAt": ISODate("2025-01-09T10:30:00Z")
}

// Collection: articles
{
  "_id": ObjectId("677c5678901234567890abcd"),
  "title": "Les nouveautés de PHP 8.4",
  "url": "https://korben.info/php-8-4-nouveautes.html",
  "description": "PHP 8.4 apporte de nouvelles fonctionnalités...",
  "source": "Korben",
  "publishedAt": ISODate("2025-01-08T14:00:00Z"),
  "createdAt": ISODate("2025-01-09T08:00:00Z")
}
```

**Avantages MongoDB :**
- Schéma flexible (code multi-langages, RSS variables)
- Arrays natifs (tags sans table de liaison)
- Performance lecture sur gros volumes
- Pas de foreign keys (référence userId en int)
- Ajout de champs sans migration

---

## 📦 Entities PostgreSQL

### 1. User
**Fichier :** `src/Entity/User.php`  
**Table :** `user_`

| Propriété | Type | Contraintes | Description |
|-----------|------|-------------|-------------|
| `id` | int (PK) | AUTO_INCREMENT | Identifiant unique |
| `email` | string(180) | NOT NULL, UNIQUE | Email de connexion |
| `password` | string(255) | NOT NULL | Mot de passe hashé (bcrypt) |
| `username` | string(100) | NOT NULL, UNIQUE | Nom d'utilisateur |
| `roles` | json | NOT NULL | Rôles utilisateur (array JSON) |
| `createdAt` | DateTimeImmutable | NOT NULL | Date de création |

**Relations :**
- OneToMany → `projects` (cascade: ['persist', 'remove'])
- OneToMany → `competences` (cascade: ['persist', 'remove'])

**Repository :** `src/Repository/UserRepository.php`

**Méthodes personnalisées :**
```php
findByEmail(string $email): ?User
findAllWithProjects(): array
```

**Valeurs par défaut :**
- `roles` : `["ROLE_USER"]`
- `createdAt` : Date du jour automatique (constructeur)

---

### 2. Project
**Fichier :** `src/Entity/Project.php`  
**Table :** `project`

| Propriété | Type | Contraintes | Description |
|-----------|------|-------------|-------------|
| `id` | int (PK) | AUTO_INCREMENT | Identifiant unique |
| `owner` | User (FK) | NOT NULL | Propriétaire du projet |
| `name` | string(255) | NOT NULL | Nom du projet |
| `description` | text | NULLABLE | Description détaillée |
| `createdAt` | DateTime | NOT NULL | Date de création |

**Relations :**
- ManyToOne → `owner` (User)
- OneToMany → `tasks` (cascade: ['persist', 'remove'])

**Repository :** `src/Repository/ProjectRepository.php`

**Méthodes personnalisées :**
```php
findByOwner(User $user): array
findByOwnerWithTasks(User $user): array
countTasksByStatus(Project $project): array
```

**Validation Symfony Validator :**
```php
#[Assert\NotBlank(message: "Le nom du projet est obligatoire")]
#[Assert\Length(max: 255, maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères")]
private ?string $name = null;

#[Assert\Length(max: 1000, maxMessage: "La description ne peut pas dépasser {{ limit }} caractères")]
private ?string $description = null;
```

---

### 3. Task
**Fichier :** `src/Entity/Task.php`  
**Table :** `task`

| Propriété | Type | Contraintes | Description |
|-----------|------|-------------|-------------|
| `id` | int (PK) | AUTO_INCREMENT | Identifiant unique |
| `project` | Project (FK) | NOT NULL | Projet parent |
| `title` | string(255) | NOT NULL | Titre de la tâche |
| `description` | text | NULLABLE | Description détaillée |
| `status` | string(50) | NOT NULL | Statut Kanban (todo, in_progress, done) |
| `position` | int | NOT NULL | Ordre d'affichage dans la colonne |
| `createdAt` | DateTime | NOT NULL | Date de création |

**Relations :**
- ManyToOne → `project` (Project)

**Repository :** `src/Repository/TaskRepository.php`

**Méthodes personnalisées :**
```php
findByProject(Project $project): array
findByProjectAndStatus(Project $project, string $status): array
findByOwner(User $user): array
getMaxPositionByProjectAndStatus(Project $project, string $status): int
```

**Validation Symfony Validator :**
```php
#[Assert\NotBlank(message: "Le titre de la tâche est obligatoire")]
#[Assert\Length(max: 255, maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères")]
private ?string $title = null;

#[Assert\Choice(choices: ['todo', 'in_progress', 'done'], message: 'Le statut doit être todo, in_progress ou done.')]
private ?string $status = null;
```

**Statuts disponibles :**
- `todo` : À faire
- `in_progress` : En cours
- `done` : Terminé

---

### 4. Competence
**Fichier :** `src/Entity/Competence.php`  
**Table :** `competence`

| Propriété | Type | Contraintes | Description |
|-----------|------|-------------|-------------|
| `id` | int (PK) | AUTO_INCREMENT | Identifiant unique |
| `owner` | User (FK) | NOT NULL | Propriétaire de la compétence |
| `name` | string(100) | NOT NULL | Nom de la compétence (ex: "PHP", "Symfony") |
| `level` | int | NOT NULL | Niveau d'auto-évaluation (1-5) |
| `notes` | text | NULLABLE | Notes personnelles |
| `projects_links` | text | NULLABLE | Liens vers projets démonstratifs |
| `snippets_links` | text | NULLABLE | Liens vers snippets associés |
| `createdAt` | DateTimeImmutable | NOT NULL | Date de création |

**Relations :**
- ManyToOne → `owner` (User)

**Repository :** `src/Repository/CompetenceRepository.php`

**Méthodes personnalisées :**
```php
findByOwner(User $user): array
findByOwnerAndLevel(User $user, int $minLevel): array
getAverageLevel(User $user): float
```

**Validation Symfony Validator :**
```php
#[Assert\NotBlank(message: "Le nom de la compétence est obligatoire")]
#[Assert\Length(max: 100, maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères")]
private ?string $name = null;

#[Assert\NotBlank(message: "Le niveau est obligatoire")]
#[Assert\Range(min: 1, max: 5, notInRangeMessage: "Le niveau doit être entre {{ min }} et {{ max }}")]
private ?int $level = null;
```

---

## 📄 Documents MongoDB

### 1. Snippet
**Fichier :** `src/Document/Snippet.php`  
**Collection :** `snippets`

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | ObjectId (PK) | Identifiant MongoDB |
| `userId` | int | Référence User (int, pas de FK) |
| `title` | string | Titre du snippet |
| `language` | string | Langage (php, js, html, css, sql, other) |
| `code` | string | Code source |
| `description` | string (nullable) | Description optionnelle |
| `tags` | array | Tags (array natif MongoDB) |
| `createdAt` | DateTimeImmutable | Date de création |

**Repository :** `src/Repository/SnippetRepository.php`

**Méthodes personnalisées :**
```php
findByUserId(int $userId): array
findByUserIdAndLanguage(int $userId, string $language): array
findByTag(string $tag): array
searchByKeyword(int $userId, string $keyword): array
```

**Langages supportés :**
- `php` : PHP
- `js` : JavaScript
- `html` : HTML
- `css` : CSS
- `sql` : SQL
- `other` : Autre

**Avantages MongoDB pour Snippets :**
- Stockage flexible du code (tous langages, toutes longueurs)
- Tags en array natif (pas de table snippet_tags)
- Recherche full-text possible sur code/description
- Ajout facile de métadonnées (ex: framework, version)

---

### 2. Article
**Fichier :** `src/Document/Article.php`  
**Collection :** `articles`

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | ObjectId (PK) | Identifiant MongoDB |
| `title` | string | Titre de l'article |
| `url` | string | URL de l'article |
| `description` | string (nullable) | Description/résumé |
| `source` | string | Source (Dev.to, Medium, Korben, etc.) |
| `publishedAt` | DateTimeImmutable | Date de publication originale |
| `createdAt` | DateTimeImmutable | Date d'import dans MY-ANKODE |

**Repository :** `src/Repository/ArticleRepository.php`

**Méthodes personnalisées :**
```php
findLatest(int $limit = 20): array
findBySource(string $source): array
findByDateRange(DateTimeImmutable $start, DateTimeImmutable $end): array
existsByUrl(string $url): bool
```

**Avantages MongoDB pour Articles :**
- Schéma flexible (RSS variables selon sources)
- Performance lecture (nombreux articles)
- Métadonnées extensibles (ajouter auteur, image, etc. sans migration)
- Évite de surcharger PostgreSQL avec des milliers d'articles

---

## 🎮 Controllers

### API REST Controllers

#### 1. ProjectController
**Fichier :** `src/Controller/ProjectController.php`  
**Prefix route :** `/api/projects`  
**Authentification :** `#[IsGranted('ROLE_USER')]`

**Endpoints :**
- `GET /api/projects` - Liste des projets de l'utilisateur
- `GET /api/projects/{id}` - Détail d'un projet
- `POST /api/projects` - Créer un projet
- `PUT /api/projects/{id}` - Modifier un projet
- `DELETE /api/projects/{id}` - Supprimer un projet

**Sécurité :**
- Vérification ownership (403 si projet appartient à un autre user)
- Validation des données (400 si données invalides)

---

#### 2. TaskController
**Fichier :** `src/Controller/TaskController.php`  
**Prefix route :** `/api/tasks`  
**Authentification :** `#[IsGranted('ROLE_USER')]`

**Endpoints :**
- `GET /api/projects/{projectId}/tasks` - Tâches d'un projet (groupées par statut)
- `GET /api/tasks/{id}` - Détail d'une tâche
- `POST /api/tasks` - Créer une tâche
- `PUT /api/tasks/{id}` - Modifier une tâche
- `PATCH /api/tasks/{id}/status` - Changer le statut d'une tâche
- `DELETE /api/tasks/{id}` - Supprimer une tâche

**Sécurité :**
- Vérification ownership via projet parent
- Validation status (todo, in_progress, done uniquement)

---

#### 3. SnippetController
**Fichier :** `src/Controller/SnippetController.php`  
**Prefix route :** `/api/snippets`  
**Authentification :** `#[IsGranted('ROLE_USER')]`  
**Base de données :** MongoDB

**Endpoints :**
- `GET /api/snippets` - Liste des snippets de l'utilisateur
- `GET /api/snippets/{id}` - Détail d'un snippet
- `POST /api/snippets` - Créer un snippet
- `PUT /api/snippets/{id}` - Modifier un snippet
- `DELETE /api/snippets/{id}` - Supprimer un snippet

**Sécurité :**
- Filtrage par userId automatique
- Validation language (php, js, html, css, sql, other)

---

#### 4. CompetenceController
**Fichier :** `src/Controller/CompetenceController.php`  
**Prefix route :** `/api/competences`  
**Authentification :** `#[IsGranted('ROLE_USER')]`

**Endpoints :**
- `GET /api/competences` - Liste des compétences de l'utilisateur
- `GET /api/competences/{id}` - Détail d'une compétence
- `POST /api/competences` - Créer une compétence
- `PUT /api/competences/{id}` - Modifier une compétence
- `DELETE /api/competences/{id}` - Supprimer une compétence

**Sécurité :**
- Vérification ownership
- Validation level (1-5 uniquement)

---

### Page Controllers (Twig)

#### 1. KanbanPageController
**Fichier :** `src/Controller/KanbanPageController.php`  
**Route :** `/kanban`  
**Template :** `templates/kanban/list.html.twig`  
**Authentification :** `#[IsGranted('ROLE_USER')]`

**Fonctionnalités :**
- Récupère tous les projets de l'utilisateur connecté
- Pour chaque projet, organise les tâches en 3 colonnes (todo, in_progress, done)
- Tri des tâches par position dans chaque colonne
- Affichage sous forme de board Kanban avec Bootstrap

**Code principal :**
```php
public function index(ProjectRepository $projectRepository): Response
{
    $projects = $projectRepository->findBy(
        ['owner' => $this->getUser()],
        ['createdAt' => 'DESC']
    );

    $projectsWithTasks = [];
    foreach ($projects as $project) {
        $tasks = $project->getTasks();
        
        $tasksByStatus = [
            'todo' => [],
            'in_progress' => [],
            'done' => []
        ];

        foreach ($tasks as $task) {
            $status = $task->getStatus();
            if (isset($tasksByStatus[$status])) {
                $tasksByStatus[$status][] = $task;
            }
        }

        foreach ($tasksByStatus as $status => $taskList) {
            usort($tasksByStatus[$status], function($a, $b) {
                return $a->getPosition() <=> $b->getPosition();
            });
        }

        $projectsWithTasks[] = [
            'project' => $project,
            'tasks' => $tasksByStatus
        ];
    }

    return $this->render('kanban/list.html.twig', [
        'projectsWithTasks' => $projectsWithTasks,
    ]);
}
```

---

#### 2. CompetencePageController
**Fichier :** `src/Controller/CompetencePageController.php`  
**Route :** `/competences`  
**Template :** `templates/competence/list.html.twig`  
**Authentification :** `#[IsGranted('ROLE_USER')]`

**Fonctionnalités :**
- Récupère toutes les compétences de l'utilisateur connecté
- Tri alphabétique par nom
- Affichage avec niveau (badge) et notes

**Code principal :**
```php
public function index(CompetenceRepository $competenceRepository): Response
{
    $competences = $competenceRepository->findBy(
        ['owner' => $this->getUser()],
        ['name' => 'ASC']
    );

    return $this->render('competence/list.html.twig', [
        'competences' => $competences,
    ]);
}
```

---

#### 3. SnippetPageController
**Fichier :** `src/Controller/SnippetPageController.php`  
**Route :** `/snippets`  
**Template :** `templates/snippet/list.html.twig`  
**Authentification :** `#[IsGranted('ROLE_USER')]`  
**Base de données :** MongoDB

**Fonctionnalités :**
- Récupère tous les snippets de l'utilisateur (MongoDB)
- Tri par date de création décroissante (plus récents d'abord)
- Affichage sous forme de cartes avec prévisualisation du code

**Code principal :**
```php
public function index(DocumentManager $dm): Response
{
    $currentUser = $this->getUser();
    
    $snippets = $dm->getRepository(Snippet::class)
        ->findBy(
            ['userId' => $currentUser->getId()],
            ['createdAt' => 'DESC']
        );

    return $this->render('snippet/list.html.twig', [
        'snippets' => $snippets,
    ]);
}
```

---

#### 4. VeilleController
**Fichier :** `src/Controller/VeilleController.php`  
**Route :** `/veille`  
**Template :** `templates/veille/list.html.twig`  
**Authentification :** `#[IsGranted('ROLE_USER')]`  
**Base de données :** MongoDB

**Fonctionnalités :**
- Récupère les 50 derniers articles RSS (MongoDB)
- Tri par date de publication décroissante
- Affichage sous forme de liste avec liens externes

**Code principal :**
```php
public function index(DocumentManager $dm): Response
{
    $articles = $dm->getRepository(Article::class)
        ->findBy(
            [],
            ['publishedAt' => 'DESC'],
            50
        );

    return $this->render('veille/list.html.twig', [
        'articles' => $articles,
    ]);
}
```

---

## 🌐 API REST Endpoints Détaillés

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

**Validation :**
- Email unique (erreur 400 si existe déjà)
- Password minimum 6 caractères
- Username unique

---

#### Connexion
```http
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response: 200 OK
Set-Cookie: PHPSESSID=abc123def456...

{
  "message": "Login successful",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "username": "JohnDoe"
  }
}
```

**Erreurs possibles :**
- 401 Unauthorized : Credentials invalides

---

#### Déconnexion
```http
GET /logout

Response: 302 Found
Location: /auth
```

---

### Projects

#### Lister les projets
```http
GET /api/projects
Authorization: Cookie (session Symfony)

Response: 200 OK
[
  {
    "id": 1,
    "name": "Blog Cuisine",
    "description": "Blog de recettes avec système de commentaires",
    "createdAt": "2025-01-06T10:00:00+00:00",
    "tasksCount": 12
  },
  {
    "id": 2,
    "name": "Dashboard Analytics",
    "description": "Tableau de bord de statistiques temps réel",
    "createdAt": "2025-01-05T14:30:00+00:00",
    "tasksCount": 8
  }
]
```

**Filtrage automatique :** Seulement les projets de l'utilisateur connecté

---

#### Créer un projet
```http
POST /api/projects
Content-Type: application/json

{
  "name": "Nouveau projet E-commerce",
  "description": "Site de vente en ligne avec paiement Stripe"
}

Response: 201 Created
{
  "id": 3,
  "name": "Nouveau projet E-commerce",
  "description": "Site de vente en ligne avec paiement Stripe",
  "createdAt": "2025-01-09T11:45:00+00:00"
}
```

**Erreurs possibles :**
- 400 Bad Request : `name` manquant ou trop long (>255 caractères)
- 401 Unauthorized : Non authentifié

---

#### Modifier un projet
```http
PUT /api/projects/3
Content-Type: application/json

{
  "name": "Projet E-commerce Symfony",
  "description": "Site de vente en ligne avec Stripe + Doctrine"
}

Response: 200 OK
{
  "id": 3,
  "name": "Projet E-commerce Symfony",
  "description": "Site de vente en ligne avec Stripe + Doctrine",
  "createdAt": "2025-01-09T11:45:00+00:00"
}
```

**Erreurs possibles :**
- 403 Forbidden : Projet appartient à un autre user
- 404 Not Found : Projet inexistant

---

#### Supprimer un projet
```http
DELETE /api/projects/3

Response: 204 No Content
```

**Cascade :** Supprime également toutes les tâches du projet (orphanRemoval: true)

**Erreurs possibles :**
- 403 Forbidden : Projet appartient à un autre user
- 404 Not Found : Projet inexistant

---

### Tasks

#### Lister les tâches d'un projet
```http
GET /api/projects/1/tasks

Response: 200 OK
{
  "todo": [
    {
      "id": 1,
      "title": "Ajouter 10 recettes",
      "description": "Créer 10 fiches recettes avec photos",
      "status": "todo",
      "position": 0,
      "createdAt": "2025-01-06T10:00:00+00:00"
    },
    {
      "id": 2,
      "title": "Tester formulaire commentaires",
      "description": "Vérifier validation + spam",
      "status": "todo",
      "position": 1,
      "createdAt": "2025-01-06T10:05:00+00:00"
    }
  ],
  "in_progress": [
    {
      "id": 3,
      "title": "Créer thème personnalisé",
      "description": "Design avec Bootstrap + couleurs custom",
      "status": "in_progress",
      "position": 0,
      "createdAt": "2025-01-06T10:10:00+00:00"
    }
  ],
  "done": [
    {
      "id": 4,
      "title": "Installer WordPress",
      "description": "Installation WP + thème de base",
      "status": "done",
      "position": 0,
      "createdAt": "2025-01-06T09:00:00+00:00"
    }
  ]
}
```

**Organisation :** Tâches groupées par statut, triées par position

---

#### Créer une tâche
```http
POST /api/tasks
Content-Type: application/json

{
  "title": "Optimiser images",
  "description": "Compresser toutes les images du site",
  "projectId": 1,
  "status": "todo"
}

Response: 201 Created
{
  "id": 5,
  "title": "Optimiser images",
  "description": "Compresser toutes les images du site",
  "status": "todo",
  "position": 2,
  "createdAt": "2025-01-09T12:00:00+00:00"
}
```

**Position automatique :** La tâche est placée en dernière position de sa colonne

**Erreurs possibles :**
- 400 Bad Request : title manquant, status invalide
- 403 Forbidden : projectId appartient à un autre user

---

#### Changer le statut d'une tâche
```http
PATCH /api/tasks/5/status
Content-Type: application/json

{
  "status": "in_progress"
}

Response: 200 OK
{
  "id": 5,
  "title": "Optimiser images",
  "status": "in_progress",
  "position": 1,
  "createdAt": "2025-01-09T12:00:00+00:00"
}
```

**Comportement :** La position est recalculée dans la nouvelle colonne

---

### Snippets (MongoDB)

#### Lister les snippets
```http
GET /api/snippets

Response: 200 OK
[
  {
    "id": "677c1234567890abcdef1234",
    "title": "Slugify Function",
    "language": "php",
    "code": "function slugify($text) {\n  return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));\n}",
    "description": "Transforme un texte en slug URL-friendly",
    "tags": ["php", "function", "string"],
    "createdAt": "2025-01-09T10:30:00+00:00"
  }
]
```

---

#### Créer un snippet
```http
POST /api/snippets
Content-Type: application/json

{
  "title": "Console Log Helper",
  "language": "js",
  "code": "const log = (msg) => console.log(`[DEBUG] ${msg}`);",
  "description": "Helper pour console.log avec préfixe",
  "tags": ["javascript", "console", "debug"]
}

Response: 201 Created
{
  "id": "677c5678901234567890abcd",
  "title": "Console Log Helper",
  "language": "js",
  "code": "const log = (msg) => console.log(`[DEBUG] ${msg}`);",
  "description": "Helper pour console.log avec préfixe",
  "tags": ["javascript", "console", "debug"],
  "createdAt": "2025-01-09T12:15:00+00:00"
}
```

---

### Competences

#### Lister les compétences
```http
GET /api/competences

Response: 200 OK
[
  {
    "id": 1,
    "name": "Symfony",
    "level": 4,
    "notes": "Maîtrise de l'API REST, Doctrine, Twig",
    "createdAt": "2025-01-06T10:00:00+00:00"
  },
  {
    "id": 2,
    "name": "Docker",
    "level": 3,
    "notes": "Docker Compose, environnements multi-conteneurs",
    "createdAt": "2025-01-06T10:05:00+00:00"
  }
]
```

---

#### Créer une compétence
```http
POST /api/competences
Content-Type: application/json

{
  "name": "Angular",
  "level": 3,
  "notes": "Composants, Services, RxJS"
}

Response: 201 Created
{
  "id": 3,
  "name": "Angular",
  "level": 3,
  "notes": "Composants, Services, RxJS",
  "createdAt": "2025-01-09T12:30:00+00:00"
}
```

**Erreurs possibles :**
- 400 Bad Request : level hors intervalle [1-5]

---

## 🖥️ Commandes console

### Tests MongoDB

#### Tester la connexion MongoDB
```bash
php bin/console app:test-mongo
```

**Fichier :** `src/Command/TestMongoCommand.php`

**Résultat attendu :**
```
Connexion MongoDB
=================

 [OK] Connexion MongoDB réussie !

 Database: my_ankode
 Collections disponibles:
  - snippets
  - articles

 Nombre de snippets: 5
 Nombre d'articles: 23
```

---

#### Insérer des données de test MongoDB
```bash
php bin/console app:test-mongo-insert
```

**Fichier :** `src/Command/TestMongoInsertCommand.php`

**Comportement :**
- Crée 1 snippet de test
- Crée 1 article de test
- Affiche les IDs créés

**Résultat attendu :**
```
Insertion de test dans MongoDB
===============================

 [OK] Snippet créé avec ID: 677c1234567890abcdef1234

 [OK] Article créé avec ID: 677c5678901234567890abcd
```

---

### Veille RSS

#### Importer un flux RSS
```bash
php bin/console app:fetch-rss <url> <source_name>
```

**Fichier :** `src/Command/FetchRssCommand.php`  
**Service :** `src/Service/RssFeedService.php`

**Exemples :**
```bash
# Flux français
php bin/console app:fetch-rss https://korben.info/feed "Korben"
php bin/console app:fetch-rss https://www.nextinpact.com/rss/news.xml "Next INpact"

# Flux anglais
php bin/console app:fetch-rss https://dev.to/feed "Dev.to"
php bin/console app:fetch-rss https://medium.com/feed/tag/javascript "Medium JS"
```

**Comportement :**
1. Parse le flux RSS XML
2. Pour chaque `<item>` :
   - Extrait title, url, description, pubDate
   - Vérifie si URL existe déjà (évite doublons)
   - Crée un document `Article` dans MongoDB
3. Affiche le nombre d'articles importés

**Résultat attendu :**
```
Récupération du flux RSS
========================

 [INFO] URL : https://korben.info/feed
 [INFO] Source : Korben
 [INFO] Utilisateur : anthony@test.com

 Téléchargement et parsing du flux...

 [OK] Flux RSS récupéré avec succès !

      20 article(s) importé(s) dans MongoDB
```

**Erreurs possibles :**
- URL invalide ou inaccessible
- Flux RSS mal formaté
- Connexion MongoDB échouée

---

## 🔧 Configuration Symfony

### Doctrine (PostgreSQL)
**Fichier :** `config/packages/doctrine.yaml`

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        server_version: '16'
        charset: utf8
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
    auto_generate_proxy_classes: true
    auto_generate_hydrator_classes: true
    connections:
        default:
            server: '%env(resolve:MONGODB_URL)%'
            options: {}
    default_database: '%env(resolve:MONGODB_DB)%'
    document_managers:
        default:
            auto_mapping: true
            mappings:
                App:
                    dir: '%kernel.project_dir%/src/Document'
                    prefix: 'App\Document'

when@prod:
    doctrine_mongodb:
        auto_generate_proxy_classes: false
        auto_generate_hydrator_classes: false
        document_managers:
            default:
                metadata_cache_driver:
                    type: service
                    id: doctrine_mongodb.system_cache_pool

    framework:
        cache:
            pools:
                doctrine_mongodb.system_cache_pool:
                    adapter: cache.system
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
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_auth
                check_path: app_auth
                enable_csrf: true
            logout:
                path: app_logout
                target: app_auth

    access_control:
        - { path: ^/auth, roles: PUBLIC_ACCESS }
        - { path: ^/register, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: ROLE_USER }
        - { path: ^/dashboard, roles: ROLE_USER }
        - { path: ^/kanban, roles: ROLE_USER }
        - { path: ^/competences, roles: ROLE_USER }
        - { path: ^/snippets, roles: ROLE_USER }
        - { path: ^/veille, roles: ROLE_USER }
```

---

### Services
**Fichier :** `config/services.yaml`

```yaml
parameters:
    env(MONGODB_URI): ''
    env(MONGODB_DB): ''

services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Document/'
            - '../src/Kernel.php'
```

**Important :** Les entités (`Entity/`) et documents (`Document/`) sont exclus de l'autowiring car ce sont des objets de données, pas des services.

---

## 🧪 Tests

### Configuration PHPUnit

**Fichier :** `phpunit.dist.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.3/phpunit.xsd"
         bootstrap="tests/Bootstrap.php"
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
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

---

### Tests unitaires (19 tests)

**Fichiers :**
- `tests/Entity/UserTest.php` (5 tests)
- `tests/Entity/ProjectTest.php` (5 tests)
- `tests/Entity/TaskTest.php` (5 tests)
- `tests/Entity/CompetenceTest.php` (4 tests)

**Ce qui est testé :**
- Getters/setters fonctionnent correctement
- Contraintes de validation (NotBlank, Length, Range, Choice)
- Valeurs par défaut (createdAt, roles, status)
- Relations entre entités (OneToMany, ManyToOne)

**Exemple UserTest.php :**
```php
public function testUserGettersAndSetters(): void
{
    $user = new User();
    
    $user->setEmail('test@example.com');
    $user->setUsername('testuser');
    $user->setPassword('hashedpassword');
    
    $this->assertEquals('test@example.com', $user->getEmail());
    $this->assertEquals('testuser', $user->getUsername());
    $this->assertEquals('hashedpassword', $user->getPassword());
}

public function testUserDefaultRoles(): void
{
    $user = new User();
    
    $this->assertEquals(['ROLE_USER'], $user->getRoles());
}
```

---

### Tests fonctionnels API REST (11 tests)

**Fichiers :**
- `tests/Controller/TaskControllerTest.php` (7 tests)
- `tests/Controller/ProjectControllerTest.php` (4 tests)

**Ce qui est testé :**
- GET : Récupération de ressources (200 OK)
- POST : Création de ressources (201 Created)
- PUT : Modification de ressources (200 OK)
- DELETE : Suppression de ressources (204 No Content)
- Codes HTTP corrects

**Exemple TaskControllerTest.php :**
```php
public function testGetTasksByProject(): void
{
    // Login avec Alice
    $this->loginAsUser('alice@test.com', 'password123');
    
    // GET /api/projects/1/tasks
    $this->client->request('GET', '/api/projects/1/tasks');
    
    $this->assertResponseIsSuccessful();
    $this->assertResponseStatusCodeSame(200);
    
    $data = json_decode($this->client->getResponse()->getContent(), true);
    
    $this->assertArrayHasKey('todo', $data);
    $this->assertArrayHasKey('in_progress', $data);
    $this->assertArrayHasKey('done', $data);
}
```

---

### Tests fonctionnels MongoDB (4 tests)

**Fichier :** `tests/Document/ArticleMongoTest.php`

**Ce qui est testé :**
- Création d'articles dans MongoDB
- Lecture d'articles par ID
- Filtrage d'articles (isolation par utilisateur si applicable)
- Suppression d'articles

---

### Tests de sécurité (13 tests)

#### Ownership Tests (4 tests)
**Fichier :** `tests/Security/OwnershipTest.php`

**Ce qui est testé :**
- User ne peut PAS voir les tasks d'un autre user (403)
- User ne peut PAS modifier le project d'un autre user (403)
- User ne peut PAS supprimer la task d'un autre user (403)
- User ne peut PAS créer une task dans le project d'un autre user (403)

**Exemple :**
```php
public function testUserCannotAccessOtherUserTasks(): void
{
    // Alice se connecte
    $this->loginAsUser('alice@test.com', 'password123');
    
    // Essaie d'accéder à une task de Marie (projet ID 4)
    $this->client->request('GET', '/api/projects/4/tasks');
    
    // Devrait être 403 Forbidden
    $this->assertResponseStatusCodeSame(403);
}
```

---

#### Validation Tests (4 tests)
**Fichier :** `tests/Security/ValidationTest.php`

**Ce qui est testé :**
- Création task sans title obligatoire (400)
- Création task avec status invalide (400)
- Création project sans name obligatoire (400)
- Task title > 255 caractères (400)

**Exemple :**
```php
public function testCreateTaskWithoutTitle(): void
{
    $this->loginAsUser('alice@test.com', 'password123');
    
    // POST /api/tasks sans title
    $this->client->request('POST', '/api/tasks', [], [], [
        'CONTENT_TYPE' => 'application/json'
    ], json_encode([
        'projectId' => 1,
        'status' => 'todo'
    ]));
    
    $this->assertResponseStatusCodeSame(400);
}
```

---

#### Authentication Tests (5 tests)
**Fichier :** `tests/Security/AuthenticationTest.php`

**Ce qui est testé :**
- GET /api/projects sans login (401 ou 302)
- POST /api/projects sans login (401 ou 302)
- GET task inexistante (404)
- DELETE project inexistant (404)
- PUT task avec données partielles (200 OK)

---

### Lancer les tests

```bash
# Tous les tests
docker-compose exec backend php bin/phpunit

# Par testsuite
docker-compose exec backend php bin/phpunit --testsuite=Entity
docker-compose exec backend php bin/phpunit --testsuite=Controller
docker-compose exec backend php bin/phpunit --testsuite=Security

# Format lisible avec détails
docker-compose exec backend php bin/phpunit --testdox

# Avec couverture de code (si xdebug installé)
docker-compose exec backend php bin/phpunit --coverage-html coverage/
```

---

## 🔒 Sécurité

### Authentification
- **Mécanisme :** Symfony Security Component
- **Hash password :** bcrypt (auto)
- **Session :** Cookie PHPSESSID
- **CSRF Protection :** Activé sur form_login

### Ownership
Tous les controllers API vérifient que la ressource appartient à l'utilisateur connecté :

```php
// Exemple dans TaskController
$task = $this->taskRepository->find($id);

if (!$task) {
    return $this->json(['error' => 'Tâche non trouvée'], Response::HTTP_NOT_FOUND);
}

// Vérification ownership via projet parent
if ($task->getProject()->getOwner() !== $this->getUser()) {
    return $this->json(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
}
```

### Validation
Contraintes Symfony Validator sur toutes les entités :
- NotBlank pour champs obligatoires
- Length pour limites de caractères
- Range pour niveaux (1-5)
- Choice pour status (todo, in_progress, done)

### Protection XSS
- Twig échappe automatiquement les variables (`{{ variable }}`)
- Utilisation de `|raw` interdite sauf cas justifiés

### Protection SQL Injection
- Doctrine Query Builder (requêtes préparées)
- Pas de requêtes SQL brutes

---

## 📚 Ressources

### Documentation officielle
- [Symfony 7.2](https://symfony.com/doc/7.2/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/)
- [Twig](https://twig.symfony.com/doc/3.x/)
- [Bootstrap 5](https://getbootstrap.com/docs/5.3/)

### Outils
- [Postman](https://www.postman.com/) - Tests API
- [MongoDB Compass](https://www.mongodb.com/products/compass) - Interface MongoDB
- [pgAdmin](https://www.pgadmin.org/) - Interface PostgreSQL

---

## 🎯 Résumé des choix techniques

### Pourquoi Symfony 7 ?
✅ Framework mature et professionnel  
✅ Doctrine ORM/ODM intégrés  
✅ Système de sécurité robuste  
✅ Twig natif pour templates  
✅ Excellente documentation  
✅ Large communauté

### Pourquoi PostgreSQL + MongoDB ?
✅ **PostgreSQL** : Relations strictes (User → Projects → Tasks), intégrité référentielle  
✅ **MongoDB** : Flexibilité (Snippets multi-langages, Articles RSS variables)  
✅ Meilleur des deux mondes selon les besoins  
✅ Performance optimale pour chaque type de données

### Pourquoi Docker ?
✅ Environnements reproductibles (dev = prod)  
✅ Pas de conflits de versions PHP/PostgreSQL/MongoDB  
✅ Déploiement simplifié  
✅ Isolation complète  
✅ Onboarding rapide (1 commande : `docker-compose up`)

### Pourquoi Bootstrap 5 ?
✅ Framework CSS mature et bien documenté  
✅ Composants prêts à l'emploi (cards, badges, forms)  
✅ Grid system responsive  
✅ Gain de temps pour le MVP certification  
✅ Rendu professionnel sans effort

---

## 👨‍💻 Auteur

**Anthony CATAN-CAVERY**  
Développeur Web et Web Mobile en formation  
📧 anthony.catan.didier@gmail.com  
🔗 [LinkedIn](https://www.linkedin.com/in/anthonycatancavery)  
💼 [GitHub](https://github.com/AnthonyCatanDidier)  
🎓 **Certification DWWM - Février 2026**

---

**📝 Dernière mise à jour :** 09 janvier 2026
