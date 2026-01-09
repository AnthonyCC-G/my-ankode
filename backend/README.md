# MY-ANKODE - Application de Productivité pour Développeurs

> Projet de certification DWWM - Développeur Web et Web Mobile

**MY-ANKODE** est une application web de productivité personnelle destinée aux développeurs juniors. Elle permet de gérer des projets en Kanban, stocker des snippets de code, suivre ses compétences techniques et effectuer une veille technologique via flux RSS.

**Auteur :** Anthony CATAN-CAVERY  
**Formation :** Titre Professionnel DWWM  
**Date :** Janvier 2026  
**Contexte :** Projet final de certification

---

## 🎯 Fonctionnalités

### Module 1 : Kanban (Gestion de projets/tâches)
- Créer et organiser des projets
- Gérer des tâches en 3 colonnes (À faire, En cours, Terminé)
- Drag & drop pour changer le statut
- Ownership : Chaque utilisateur voit uniquement ses projets

### Module 2 : Snippets (Bibliothèque de code)
- Stocker des morceaux de code réutilisables
- Support multi-langages (PHP, JS, HTML, CSS, SQL)
- Tags pour organiser les snippets
- Recherche et filtrage

### Module 3 : Compétences (Lutte contre le syndrome de l'imposteur)
- Auto-évaluation des compétences techniques (niveau 1-5)
- Suivi de progression
- Notes personnelles sur chaque compétence

### Module 4 : Veille Technologique
- Agrégation de flux RSS tech (Korben, Dev.to, Medium, etc.)
- Centralisation des articles
- Marquage lu/non-lu

---

## 🛠️ Stack Technique

### Backend
- **Framework :** Symfony 7.2 (PHP 8.3)
- **Bases de données :**
  - PostgreSQL 16 (Users, Projects, Tasks, Competences)
  - MongoDB 6 (Snippets, Articles RSS)
- **ORM/ODM :** Doctrine ORM + Doctrine MongoDB ODM
- **Authentification :** Symfony Security (bcrypt)
- **Templating :** Twig 3 + Bootstrap 5

### Frontend (MVP Certification)
- Twig Templates
- Bootstrap 5
- JavaScript Vanilla

### DevOps
- Docker + Docker Compose
- Environnement dev : PHP built-in server (port 8000)
- Environnement prod : Nginx + PHP-FPM (port 80)

### Tests
- PHPUnit 11
- 47 tests automatisés (entités, API, sécurité)

---

## 🗂️ Architecture

### Architecture Hybride PostgreSQL + MongoDB

**PostgreSQL (Relationnel) :**
- Entités avec relations strictes (User → Projects → Tasks → Competences)
- Intégrité référentielle garantie
- Cascade delete (supprimer user → supprimer ses projects)

**MongoDB (Documentaire) :**
- Documents flexibles (Snippets multi-langages, Articles RSS variables)
- Arrays natifs (tags sans table de liaison)
- Performance lecture sur gros volumes
```
User (PostgreSQL)
 ├── Projects (PostgreSQL)
 │    └── Tasks (PostgreSQL)
 └── Competences (PostgreSQL)

User (référence string userId)
 ├── Snippets (MongoDB)
 └── Articles favoris (MongoDB)
```

---

## 🚀 Installation

### Prérequis
- Docker + Docker Compose
- Git

### Étapes
```bash
# 1. Cloner le projet
git clone https://github.com/AnthonyCatanDidier/my-ankode.git
cd my-ankode

# 2. Lancer Docker
docker-compose up -d

# 3. Entrer dans le conteneur backend
docker-compose exec backend sh

# 4. Installer les dépendances
composer install

# 5. Créer la base PostgreSQL
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Charger les fixtures (données de test)
php bin/console doctrine:fixtures:load --no-interaction

# 7. Vérifier MongoDB
php bin/console app:test-mongo

# 8. (Optionnel) Importer des articles RSS
php bin/console app:fetch-rss https://korben.info/feed "Korben"

exit
```

### Accéder à l'application
- **URL :** http://localhost:8000
- **Connexion test :**
  - Email : `alice@test.com`
  - Password : `password123`

---

## 🌐 Routes Disponibles

### Pages HTML (Twig)
| Route | Description | Méthode | Authentification |
|-------|-------------|---------|------------------|
| `/auth` | Page de connexion | GET | Public |
| `/register` | Inscription | GET/POST | Public |
| `/dashboard` | Tableau de bord | GET | Requis |
| `/kanban` | Board Kanban | GET | Requis |
| `/competences` | Liste compétences | GET | Requis |
| `/snippets` | Bibliothèque snippets | GET | Requis |
| `/veille` | Flux RSS | GET | Requis |

### API REST (JSON)
| Route | Description | Méthode | Authentification |
|-------|-------------|---------|------------------|
| `/api/projects` | CRUD Projets | GET/POST/PUT/DELETE | Requis |
| `/api/tasks` | CRUD Tâches | GET/POST/PUT/DELETE | Requis |
| `/api/competences` | CRUD Compétences | GET/POST/PUT/DELETE | Requis |
| `/api/snippets` | CRUD Snippets | GET/POST/PUT/DELETE | Requis |

**Sécurité :** Toutes les routes API vérifient l'ownership (403 si accès à une ressource d'un autre utilisateur).

---

## 🧪 Tests

### Lancer les tests
```bash
# Script complet (fixtures + cache + tests)
./scripts/check-tests.sh

# Ou manuellement
docker-compose exec backend php bin/phpunit
```

### Couverture des tests

**47 tests automatisés PHPUnit :**
- ✅ **19 tests unitaires** : Validation entités (User, Project, Task, Competence)
- ✅ **15 tests fonctionnels** : API REST + MongoDB (CRUD complet)
- ✅ **13 tests de sécurité** : Ownership (403), Validation (400), Authentification (401)

**Résultat attendu :** `OK (47 tests, 134 assertions)`

---

## 📁 Structure du Projet
```
my-ankode/
├── backend/                      # Application Symfony 7
│   ├── config/                   # Configuration (security, doctrine, routes)
│   ├── migrations/               # Migrations PostgreSQL
│   ├── public/                   # Point d'entrée (index.php)
│   ├── src/
│   │   ├── Controller/           # Controllers API + Pages
│   │   │   ├── ProjectController.php    # API REST Projects
│   │   │   ├── TaskController.php       # API REST Tasks
│   │   │   ├── SnippetController.php    # API REST Snippets (MongoDB)
│   │   │   ├── CompetenceController.php # API REST Competences
│   │   │   ├── KanbanPageController.php       # Page Kanban
│   │   │   ├── CompetencePageController.php   # Page Compétences
│   │   │   ├── SnippetPageController.php      # Page Snippets
│   │   │   └── VeilleController.php           # Page Veille RSS
│   │   ├── Entity/               # Entités PostgreSQL (User, Project, Task, Competence)
│   │   ├── Document/             # Documents MongoDB (Snippet, Article)
│   │   ├── Repository/           # Repositories Doctrine
│   │   ├── Command/              # Commandes console (fetch-rss, test-mongo)
│   │   └── Security/             # Authenticator
│   ├── templates/                # Templates Twig
│   │   ├── auth/                 # Connexion/Inscription
│   │   ├── dashboard/            # Tableau de bord
│   │   ├── kanban/               # Board Kanban
│   │   ├── competence/           # Liste compétences
│   │   ├── snippet/              # Bibliothèque snippets
│   │   └── veille/               # Flux RSS
│   ├── tests/                    # Tests PHPUnit (47 tests)
│   │   ├── Entity/               # Tests unitaires (19)
│   │   ├── Controller/           # Tests API REST (11)
│   │   ├── Document/             # Tests MongoDB (4)
│   │   └── Security/             # Tests sécurité (13)
│   └── var/                      # Cache, logs
├── docker-compose.yml            # Configuration Docker
├── .env                          # Variables d'environnement
└── README.md                     # Ce fichier
```

---

## 📚 Documentation Complémentaire

- **[TECHNICAL_DETAILS.md](TECHNICAL_DETAILS.md)** - Documentation technique détaillée (API, entités, MongoDB)
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Architecture 3-tiers complète
- **[DECISIONS.md](DECISIONS.md)** - Justifications des choix techniques

---

## 🎓 Compétences DWWM Validées

**Référentiel TP DWWM (Niveau 5) :**

### CCP 1 : Développer la partie front-end d'une application web ou web mobile en intégrant les recommandations de sécurité
✅ Maquetter une application  
✅ Réaliser une interface utilisateur web statique et adaptable (Bootstrap 5, Twig)  
✅ Développer une interface utilisateur web dynamique  
✅ Réaliser une interface utilisateur avec une solution de gestion de contenu ou e-commerce  

### CCP 2 : Développer la partie back-end d'une application web ou web mobile en intégrant les recommandations de sécurité
✅ Créer une base de données (PostgreSQL + MongoDB)  
✅ Développer les composants d'accès aux données (Repositories Doctrine)  
✅ Développer la partie back-end d'une application web ou web mobile (Symfony 7, API REST)  
✅ Élaborer et mettre en œuvre des composants dans une application de gestion de contenu ou e-commerce  

### Sécurité & Tests
✅ Authentification (Symfony Security)  
✅ Ownership (utilisateur ne peut modifier que ses propres ressources)  
✅ Validation des données (Symfony Validator)  
✅ Tests automatisés (47 tests PHPUnit)  

---

## 👨‍💻 Auteur

**Anthony CATAN-CAVERY**  
Développeur Web et Web Mobile en formation  
🔗 [LinkedIn](https://www.linkedin.com/in/anthonycatancavery)  
🎓 **Certification DWWM - Février 2026**

---

## 📝 Évolution Future (Post-Certification)

**Frontend Angular (bonus) :**
- Migration progressive des pages Twig vers Angular 18
- API REST déjà prête pour consommation par SPA
- Architecture découplée frontend/backend

**Déploiement :**
- Hébergement : VPS ou cloud (AWS, DigitalOcean)
- CI/CD : GitHub Actions
- Monitoring : Sentry, logs centralisés

---

**Dernière mise à jour :** 09 janvier 2026