# MY-ANKODE - Backend Symfony 7

**API REST pour application développeurs juniors** - Projet de certification DWWM

---

## 🚀 Démarrage rapide

### Avec Docker (recommandé)
```bash
# Lancer les conteneurs
docker-compose up -d

# Accéder au conteneur backend
docker-compose exec backend sh

# Lancer le serveur
php -S 0.0.0.0:8000 -t public
```

### Sans Docker
```bash
# Installer les dépendances
composer install

# Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres

# Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Lancer le serveur
symfony serve
```

**URL de l'application :** http://localhost:8000

---

## 🎯 Routes disponibles

### Routes publiques
| Route | Méthode | Description |
|-------|---------|-------------|
| `/` | GET | Redirection vers `/auth` ou `/dashboard` selon état connexion |
| `/auth` | GET | Page d'authentification unifiée (inscription + connexion) |
| `/register` | POST | Traitement inscription |
| `/login` | POST | Traitement connexion |

### Routes authentifiées
| Route | Méthode | Description |
|-------|---------|-------------|
| `/logout` | GET | Déconnexion utilisateur |
| `/dashboard` | GET | Page d'accueil utilisateur connecté |

---

## 🔐 Architecture d'authentification

### Entité User
- `email` (unique, not null) - Email de connexion
- `username` (unique, not null) - Nom d'utilisateur
- `password` (hashed bcrypt) - Mot de passe sécurisé
- `roles` (JSON) - Rôles utilisateur
- `createdAt` (datetime) - Date de création

### Controllers
- **AuthController** : Affiche la page `/auth` avec les 2 formulaires
- **RegistrationController** : Traite l'inscription (POST `/register`)
- **SecurityController** : Traite la connexion (POST `/login`)
- **DashboardController** : Affiche le dashboard après connexion

### Formulaires
- **RegistrationFormType** : `username`, `email`, `password`, `agreeTerms`
- Connexion : Formulaire manuel Twig (email + password)

### Sécurité (security.yaml)
- **Hash** : bcrypt automatique
- **Authenticator** : AppCustomAuthenticator (email + password)
- **Protection CSRF** : Token `csrf_token('authenticate')`
- **Remember Me** : Option "Se souvenir de moi" configurée

---

## 📊 Entities PostgreSQL (src/Entity/)

### User
**Table :** `user_`

**Champs :**
- `id` (PK, auto-increment)
- `email` (string, unique)
- `username` (string, unique)
- `password` (string, hashed)
- `roles` (json)
- `createdAt` (datetime)

**Relations :**
- `projects` → OneToMany vers Project

---

### Project
**Table :** `project`

**Champs :**
- `id` (PK, auto-increment)
- `name` (string) - Nom du projet
- `description` (text, nullable) - Description détaillée
- `status` (string, default: 'active') - Statut : active | archived
- `createdAt` (datetime)
- `updatedAt` (datetime)

**Relations :**
- `user` → ManyToOne vers User (CASCADE on delete)
- `tasks` → OneToMany vers Task

---

### Task
**Table :** `task`

**Champs :**
- `id` (PK, auto-increment)
- `title` (string) - Titre de la tâche
- `description` (text, nullable) - Description détaillée
- `status` (string, default: 'todo') - Statut : todo | in_progress | done
- `priority` (string, default: 'medium') - Priorité : low | medium | high
- `dueDate` (datetime, nullable) - Date limite
- `createdAt` (datetime)
- `updatedAt` (datetime)

**Relations :**
- `project` → ManyToOne vers Project (CASCADE on delete)

---

## 🗄️ MongoDB (NoSQL)

### Configuration

**Version :** 6.0  
**Port :** 27017  
**Base de données :** my_ankode  
**Driver PHP :** Doctrine MongoDB ODM 2.x

**Collections :**
- `snippets` - Extraits de code avec annotations
- `articles` - Articles de veille technologique (flux RSS)

### Configuration (doctrine_mongodb.yaml)
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
                    dir: '%kernel.project_dir%/src/Document'
```

### Commandes utiles
```bash
# Tester la connexion MongoDB
php bin/console app:test-mongo

# Insérer des données de test
php bin/console app:test-mongo-insert

# Lister les Documents mappés
php bin/console doctrine:mongodb:mapping:info
```

---

## 📄 Documents MongoDB (src/Document/)

### Snippet
**Collection :** `snippets`

**Champs :**
- `id` (ObjectId auto-généré)
- `title` (string) - Titre du snippet
- `language` (string) - Langage de programmation (PHP, JavaScript, etc.)
- `code` (string) - Code source
- `description` (string, nullable) - Annotations et explications
- `tags` (array) - Mots-clés (ex: ['PHP', 'PostgreSQL', 'PDO'])
- `createdAt` (datetime)
- `user` (ReferenceOne → User PostgreSQL)

**Exemple de document :**
```json
{
  "_id": "69469a40641a1d4aa0010e11",
  "title": "Connexion PostgreSQL en PHP",
  "language": "PHP",
  "code": "$pdo = new PDO(\"pgsql:host=localhost;dbname=test\", \"user\", \"pass\");",
  "description": "Exemple de connexion à PostgreSQL avec PDO",
  "tags": ["PHP", "PostgreSQL", "PDO", "Database"],
  "createdAt": "2024-12-20T11:52:00Z",
  "user": "1"
}
```

---

### Article
**Collection :** `articles`

**Champs :**
- `id` (ObjectId auto-généré)
- `title` (string) - Titre de l'article
- `url` (string) - URL source de l'article
- `description` (string, nullable) - Résumé/extrait
- `source` (string, nullable) - Nom du site (ex: "Dev.to", "Medium")
- `tags` (array) - Catégories (ex: ['Symfony', 'PHP', 'Framework'])
- `publishedAt` (datetime, nullable) - Date de publication originale
- `createdAt` (datetime) - Date d'ajout dans MY-ANKODE
- `isRead` (bool, default: false) - Article lu ou non
- `user` (ReferenceOne → User PostgreSQL)

**Exemple de document :**
```json
{
  "_id": "69469a41641a1d4aa0010e13",
  "title": "Les nouveautés de Symfony 7",
  "url": "https://symfony.com/blog/symfony-7-0-released",
  "description": "Symfony 7.0 apporte de nombreuses améliorations...",
  "source": "Symfony Blog",
  "tags": ["Symfony", "PHP", "Framework"],
  "publishedAt": "2023-11-30T00:00:00Z",
  "createdAt": "2024-12-20T11:52:00Z",
  "isRead": false,
  "user": "1"
}
```

---

## 🧪 Tests

### Tests d'authentification
```bash
# Page d'authentification
http://localhost:8000/auth

# Test inscription
1. Aller sur /auth
2. Remplir le formulaire gauche (S'inscrire)
3. Soumettre → Redirection vers /dashboard

# Test connexion
1. Aller sur /auth
2. Remplir le formulaire droit (Se connecter)
3. Soumettre → Redirection vers /dashboard

# Test déconnexion
http://localhost:8000/logout → Redirection vers /auth
```

### Tests MongoDB
```bash
# Vérifier connexion
php bin/console app:test-mongo

# Insérer données de test
php bin/console app:test-mongo-insert

# Résultat attendu :
# - 1 Snippet créé
# - 1 Article créé
# - Collections 'snippets' et 'articles' visibles
```

---

## 📁 Structure des fichiers
```
backend/
├── src/
│   ├── Command/
│   │   ├── TestMongoCommand.php           # Test connexion MongoDB
│   │   └── TestMongoInsertCommand.php     # Insert test MongoDB
│   ├── Controller/
│   │   ├── AuthController.php             # Affiche /auth
│   │   ├── RegistrationController.php     # Traite inscription
│   │   ├── SecurityController.php         # Traite connexion
│   │   └── DashboardController.php        # Dashboard connecté
│   ├── Entity/
│   │   ├── User.php                       # Entity User (PostgreSQL)
│   │   ├── Project.php                    # Entity Project
│   │   └── Task.php                       # Entity Task
│   ├── Document/
│   │   ├── Snippet.php                    # Document Snippet (MongoDB)
│   │   └── Article.php                    # Document Article (MongoDB)
│   ├── Form/
│   │   └── RegistrationFormType.php       # Formulaire inscription
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── ProjectRepository.php
│   │   └── TaskRepository.php
│   └── Security/
│       └── AppCustomAuthenticator.php     # Authentification custom
├── config/
│   └── packages/
│       ├── doctrine.yaml                  # Config PostgreSQL
│       ├── doctrine_mongodb.yaml          # Config MongoDB
│       └── security.yaml                  # Config sécurité
├── migrations/                            # Migrations PostgreSQL
│   ├── Version20241216135401.php          # Table user_
│   ├── Version20241219123456.php          # Tables project + task
│   └── ...
├── templates/
│   ├── auth/
│   │   └── index.html.twig                # Page auth unifiée
│   └── dashboard/
│       └── index.html.twig                # Dashboard
├── public/
│   ├── css/
│   │   └── auth.css                       # Styles personnalisés
│   └── images/                            # Assets visuels
├── composer.json                          # Dépendances PHP
└── .env                                   # Variables d'environnement
```

---

## 🛠️ Commandes Symfony utiles

### Base de données PostgreSQL
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Générer une migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Lister les entités mappées
php bin/console doctrine:mapping:info
```

### MongoDB
```bash
# Lister les documents mappés
php bin/console doctrine:mongodb:mapping:info

# Test connexion MongoDB
php bin/console app:test-mongo

# Insérer données de test
php bin/console app:test-mongo-insert
```

### Cache
```bash
# Vider le cache
php bin/console cache:clear

# Vider le cache sans warmup
php bin/console cache:clear --no-warmup

# Warmup manuel
php bin/console cache:warmup
```

---

## 📦 Stack Technique Backend

- **Framework** : Symfony 7
- **PHP** : 8.3+
- **Databases** :
  - PostgreSQL 16 (Relationnel)
  - MongoDB 6 (Documentaire)
- **ORM/ODM** :
  - Doctrine ORM (PostgreSQL)
  - Doctrine MongoDB ODM (MongoDB)
- **Authentification** : Symfony Security + bcrypt
- **Frontend Templates** : Twig + Bootstrap 5

---

## ✅ Checklist de développement

### Sprint 1 (Terminé ✅)
- [x] Carte #10 : Entités User + Auth (PostgreSQL)
- [x] Carte #11 : Interface Frontend Auth
- [x] Carte #12 : Entités Project & Task
- [x] Carte #17 : Configuration MongoDB + Connexion
- [x] Documents Snippet & Article créés
- [x] Commandes test MongoDB

### Sprint 2 (À venir)
- [ ] Carte #13 : API REST CRUD Projects
- [ ] Carte #14 : API REST CRUD Tasks
- [ ] Carte #18 : CRUD Snippets (MongoDB)
- [ ] Carte #19 : Module Veille (Articles RSS)

---

**Dernière mise à jour :** 20/12/2024 - MongoDB configuré ✅