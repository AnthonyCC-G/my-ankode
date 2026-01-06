# MY-ANKODE

[![Documentation](https://img.shields.io/badge/docs-architecture-blue?style=for-the-badge&logo=readthedocs&logoColor=white)](./ARCHITECTURE.md)
[![Symfony](https://img.shields.io/badge/Symfony-7-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![Docker](https://img.shields.io/badge/Docker-Dev%20%2B%20Prod-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![MongoDB](https://img.shields.io/badge/MongoDB-6-47A248?style=for-the-badge&logo=mongodb&logoColor=white)](https://www.mongodb.com/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-Tests-3776AB?style=for-the-badge&logo=php&logoColor=white)](https://phpunit.de/)
[![License](https://img.shields.io/badge/license-Educational-green?style=for-the-badge)](./LICENSE)

> **Projet de certification DWWM (Développeur Web et Web Mobile) - Niveau 5 (Bac+2)**  
> Application web complète pour développeurs juniors - Stack Backend Symfony + Frontend Twig/Bootstrap/Vanilla JS

---

## 📋 Description

MY-ANKODE est une application tout-en-un destinée aux développeurs juniors, proposant **4 modules complémentaires** :

- 📊 **Gestion de projets** : Kanban pour organiser vos tâches (À faire / En cours / Terminé)
- 💾 **Bibliothèque de code** : Snippets avec annotations et catégorisation par langage
- 📰 **Veille technologique** : Agrégation automatique de flux RSS (Dev.to, Medium, Korben, etc.)
- 🎯 **Suivi de compétences** : Profil développeur et auto-évaluation (niveaux 1-5)

**🎓 Contexte :** Ce projet démontre la maîtrise des **8 compétences DWWM** via une architecture hybride SQL/NoSQL et un déploiement Docker professionnel.

---

## 🛠️ Stack Technique

### 🎯 MVP Certification (Version Actuelle)

#### Backend
- **Framework** : Symfony 7 (PHP 8.3+)
- **Architecture** : API REST (JSON)
- **Authentification** : Symfony Security + bcrypt
- **Bases de données** : 
  - **PostgreSQL 16** (Relationnel) → User, Project, Task, Competence
  - **MongoDB 6** (Documentaire) → Snippet, Article
- **ORM/ODM** : Doctrine ORM + Doctrine MongoDB ODM

#### Frontend
- **Templating** : Twig (moteur natif Symfony)
- **UI Framework** : Bootstrap 5 (responsive mobile-first)
- **Interactivité** : JavaScript Vanilla (Fetch API, DOM manipulation)
- **Styling** : CSS personnalisé (palette cyan #00C2D1 / orange #FDAB5E)

#### DevOps & Tests
- **Conteneurisation** : Docker + Docker Compose
- **Environnements** : Dev (PHP built-in) + Prod (Nginx + PHP-FPM)
- **Tests** : PHPUnit (tests unitaires backend)
- **Versioning** : Git + GitHub (Git Flow)

---

### 🚀 Évolution Post-Certification (Roadmap Future)

#### Migration Frontend Progressive
- **Framework** : Angular 18 (TypeScript)
- **State Management** : RxJS + Services Angular
- **UI Library** : ng-bootstrap
- **Tests** : Jasmine + Karma

**📌 Pourquoi cette évolution ?**
- ✅ **Stratégie MVP-first** : Valider la certification avec une stack maîtrisée et fonctionnelle
- ✅ **Architecture API REST** : Backend déjà découplé, migration frontend facilitée
- ✅ **Apprentissage ciblé** : Se concentrer sur Angular après avoir consolidé les bases Symfony/PostgreSQL/MongoDB
- ✅ **Marché régional** : 80% des offres d'emploi en Hauts-de-France requièrent Angular (compétence stratégique post-certif)

**⏱️ Timeline :** Février 2026 → Été 2026 (migration progressive module par module)

---

## 🗄️ Architecture Hybride PostgreSQL + MongoDB

MY-ANKODE utilise une **architecture de données hybride** pour tirer parti des forces de chaque technologie.

### PostgreSQL (Relationnel)
**Usage :** Données structurées nécessitant une intégrité référentielle stricte

- 👤 **USER** : Utilisateurs et authentification
- 📁 **PROJECT** : Projets utilisateur (1 user → N projects)
- ✅ **TASK** : Tâches en mode Kanban (1 project → N tasks)
- 🎯 **COMPETENCE** : Compétences et auto-évaluation (1 user → N competences)

**Avantages :**
- Relations strictes avec CASCADE
- Intégrité référentielle garantie
- Transactions ACID
- Requêtes SQL optimisées (JOIN)

### MongoDB (Documentaire)
**Usage :** Données flexibles et volumineuses sans relations complexes

- 💾 **SNIPPET** : Extraits de code avec annotations
  - Stockage flexible du code (multi-langages)
  - Tags en array natif (pas de table de liaison)
  - Référence légère vers User (pas de foreign key)
  
- 📰 **ARTICLE** : Articles de veille technologique
  - Contenu RSS variable selon les sources
  - Métadonnées extensibles
  - Pas de schéma rigide requis

**Avantages :**
- Flexibilité du schéma (JSON natif)
- Tableaux et objets imbriqués
- Performance sur gros volumes
- Recherche full-text native

### Justification Architecturale
Pour une analyse détaillée de ce choix technique, consultez [DECISIONS.md](./DECISIONS.md)

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| **[ARCHITECTURE.md](./ARCHITECTURE.md)** | Architecture 3-tiers, modules Symfony, endpoints API REST |
| **[DECISIONS.md](./DECISIONS.md)** | Justification architecture hybride PostgreSQL + MongoDB |
| **[backend/README.md](./backend/README.md)** | Documentation technique backend (Entities, Documents, API Routes) |
| **[`/docs/schemas/`](./docs/schemas/)** | Diagrammes UML et Merise (MCD, MLD, classes) |
| **[`/docs/maquettes/`](./docs/maquettes/)** | Maquettes Figma des interfaces utilisateur |

📖 **Pour comprendre l'organisation du code**, consultez [ARCHITECTURE.md](./ARCHITECTURE.md)

---

## 🚀 Installation

### Prérequis
- **Docker Desktop** (recommandé) ✅
- **OU** : PHP 8.3+, Composer, PostgreSQL 16, MongoDB 6

---

### Option 1 : Installation avec Docker (RECOMMANDÉ)

```bash
# 1. Cloner le repository
git clone https://github.com/ton-username/my-ankode.git
cd my-ankode

# 2. Lancer l'environnement de développement
docker-compose up -d

# 3. Installer les dépendances Backend
docker-compose exec backend sh
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
exit

# 4. Accéder à l'application
# Backend : http://localhost:8000/auth
# PostgreSQL : localhost:5432
# MongoDB : localhost:27017
```

**Tests MongoDB :**
```bash
docker-compose exec backend sh
php bin/console app:test-mongo          # Test connexion
php bin/console app:test-mongo-insert   # Insérer données de test
```

---

### Option 2 : Installation manuelle

#### Backend (Symfony)
```bash
cd backend
composer install
cp .env .env.local
# Éditer .env.local avec vos paramètres (voir ci-dessous)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve
```

**Configuration `.env.local` :**
```env
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/my_ankode?serverVersion=16&charset=utf8"
MONGODB_URL="mongodb://127.0.0.1:27017"
MONGODB_DB="my_ankode"
```

---

## 🐳 Environnements Docker (Dev vs Prod)

MY-ANKODE propose **deux environnements Docker distincts** pour refléter les pratiques professionnelles réelles.

### 🔧 Environnement de Développement (dev)

**Fichier :** `docker-compose.yml`  
**Port Backend :** 8000  
**Serveur web :** PHP built-in server (`php -S 0.0.0.0:8000`)

```bash
# Démarrer
docker-compose up -d

# Accéder
http://localhost:8000/auth
```

**Caractéristiques :**
- ✅ Hot-reload (modifications visibles instantanément)
- ✅ Volumes montés (code modifiable depuis Windows/Linux)
- ✅ Debug activé (APP_DEBUG=1)
- ✅ Logs verbeux pour débogage
- ✅ OPcache désactivé (développement)

**📝 Note :** Le serveur PHP intégré remplace Symfony CLI qui a des problèmes de compatibilité avec Docker (écoute sur 127.0.0.1 uniquement). Cette solution simple convient parfaitement au développement.

---

### 🚀 Environnement de Production (prod)

**Fichier :** `docker-compose.prod.yml`  
**Port :** 80  
**Serveur web :** Nginx + PHP-FPM

```bash
# Démarrer
docker-compose -f docker-compose.prod.yml up -d

# Accéder
http://localhost/auth
```

**Caractéristiques :**
- ✅ Nginx (serveur web professionnel optimisé)
- ✅ PHP-FPM (gestionnaire de processus performant)
- ✅ OPcache activé (cache bytecode 256MB)
- ✅ Code compilé dans l'image Docker
- ✅ Debug désactivé (APP_DEBUG=0)
- ✅ Restart automatique (`unless-stopped`)

**Architecture :**
```
Client → Nginx:80 → PHP-FPM:9000 → Symfony 7
                                      ↓
                              PostgreSQL + MongoDB
```

---

### 🔄 Basculer entre environnements

```bash
# Dev → Prod
docker-compose down
docker-compose -f docker-compose.prod.yml up -d

# Prod → Dev
docker-compose -f docker-compose.prod.yml down
docker-compose up -d
```

---

## 🌐 URLs selon l'environnement

### Développement (`docker-compose.yml`)
- **Backend** : http://localhost:8000
- **Page auth** : http://localhost:8000/auth
- **Dashboard** : http://localhost:8000/dashboard
- **PostgreSQL** : localhost:5432
- **MongoDB** : localhost:27017

### Production (`docker-compose.prod.yml`)
- **Application** : http://localhost
- **Page auth** : http://localhost/auth
- **Dashboard** : http://localhost/dashboard
- **PostgreSQL** : localhost:5432 *(conteneur interne)*
- **MongoDB** : localhost:27017 *(conteneur interne)*

---

## 🗂️ Structure du projet

```
my-ankode/
├── backend/                   # API Symfony 7
│   ├── src/
│   │   ├── Command/           # Commandes console (test MongoDB, RSS)
│   │   ├── Controller/        # Controllers API REST + Pages Twig
│   │   ├── Entity/            # Entities Doctrine (PostgreSQL)
│   │   ├── Document/          # Documents MongoDB ODM
│   │   ├── Repository/        # Repositories (ORM + ODM)
│   │   ├── Security/          # Authentification (Authenticator, Voters)
│   │   └── Service/           # Services métier (RssFeedService, etc.)
│   ├── templates/             # Templates Twig (auth, dashboard, kanban, etc.)
│   ├── public/                # Assets publics (CSS, JS, images)
│   ├── config/
│   │   └── packages/
│   │       ├── doctrine.yaml           # Config PostgreSQL
│   │       ├── doctrine_mongodb.yaml   # Config MongoDB
│   │       └── security.yaml           # Config authentification
│   ├── migrations/            # Migrations PostgreSQL
│   ├── tests/                 # Tests PHPUnit
│   ├── Dockerfile             # Image Docker dev
│   ├── Dockerfile.prod        # Image Docker production
│   └── README.md              # Documentation backend
├── nginx/                     # Configuration Nginx production
│   └── default.conf           # Routing Symfony + sécurité
├── docs/                      # Documentation projet
│   ├── schemas/               # Diagrammes UML/Merise (MCD, MLD, classes)
│   └── maquettes/             # Maquettes Figma (PNG/PDF)
├── docker-compose.yml         # Environnement DEV
├── docker-compose.prod.yml    # Environnement PROD
├── ARCHITECTURE.md            # Architecture technique détaillée
├── DECISIONS.md               # Décisions architecturales justifiées
└── README.md                  # Ce fichier
```

---

## 🧪 Tests

### Tests d'authentification
```bash
# Accéder à la page d'authentification
http://localhost:8000/auth

# Inscription : Formulaire gauche → Redirection /dashboard
# Connexion : Formulaire droit → Redirection /dashboard
# Déconnexion : http://localhost:8000/logout → /auth
```

### Tests API REST

#### Projects
```bash
# Lister les projets de l'utilisateur connecté
GET http://localhost:8000/api/projects
Authorization: Cookie (session Symfony)

# Créer un projet
POST http://localhost:8000/api/projects
Body: {"name": "Mon projet", "description": "Description"}

# Modifier un projet
PUT http://localhost:8000/api/projects/{id}
Body: {"name": "Nouveau nom"}

# Supprimer un projet
DELETE http://localhost:8000/api/projects/{id}
```

#### Tasks
```bash
# Lister les tâches d'un projet
GET http://localhost:8000/api/projects/{projectId}/tasks

# Créer une tâche
POST http://localhost:8000/api/tasks
Body: {
  "title": "Ma tâche",
  "description": "Description",
  "projectId": 1,
  "status": "todo"
}

# Changer le statut d'une tâche
PATCH http://localhost:8000/api/tasks/{id}/status
Body: {"status": "in_progress"}

# Supprimer une tâche
DELETE http://localhost:8000/api/tasks/{id}
```

#### Snippets (MongoDB)
```bash
# Lister les snippets de l'utilisateur
GET http://localhost:8000/api/snippets

# Créer un snippet
POST http://localhost:8000/api/snippets
Body: {
  "title": "Fonction utile",
  "language": "php",
  "code": "function example() { return true; }",
  "description": "Description optionnelle"
}

# Modifier un snippet
PUT http://localhost:8000/api/snippets/{id}
Body: {"title": "Nouveau titre"}

# Supprimer un snippet
DELETE http://localhost:8000/api/snippets/{id}
```

**Langages supportés** : `js`, `php`, `html`, `css`, `sql`, `other`

### Tests MongoDB
```bash
# Entrer dans le conteneur backend
docker-compose exec backend sh

# Tester la connexion MongoDB
php bin/console app:test-mongo
# Résultat attendu :
# ✅ Connexion MongoDB réussie
# 🗄️ Base : my_ankode
# 📂 Collections : snippets, articles

# Insérer des données de test
php bin/console app:test-mongo-insert
# Résultat attendu :
# ✅ 1 Snippet créé
# ✅ 1 Article créé
```

### Veille RSS
```bash
docker-compose exec backend sh

# Tester flux français
php bin/console app:fetch-rss https://korben.info/feed "Korben"

# Tester flux anglais
php bin/console app:fetch-rss https://dev.to/feed "Dev.to"

# Vérifier les articles créés
docker-compose exec mongo mongosh my_ankode --eval "db.articles.countDocuments()"
```

### Tests Unitaires Backend (PHPUnit)
```bash
cd backend
php bin/phpunit

# Tester une classe spécifique
php bin/phpunit tests/Entity/UserTest.php
```

---

## 🛣️ Roadmap

### ✅ Sprint 1 & 2 : Architecture & Backend (Terminé)
- [x] Setup environnement (Symfony + Docker dev + prod)
- [x] Configuration bases de données (PostgreSQL + MongoDB)
- [x] Modélisation UML et Merise (MCD, MLD, diagramme classes)
- [x] Authentification Backend (User entity + Security)
- [x] Authentification Frontend (Templates Twig /auth)
- [x] Entities : User, Project, Task, Competence (PostgreSQL)
- [x] Documents : Snippet, Article (MongoDB)
- [x] API REST CRUD Projects & Tasks
- [x] API REST CRUD Snippets (MongoDB)
- [x] Service Veille RSS (Commande Symfony)

### 🔄 Sprint 3 : Finalisation Backend (En cours)
- [x] API REST CRUD Competences
- [ ] Tests unitaires PHPUnit (User, Project, Task, Competence, Snippet)
- [ ] Fixtures pour données de test

### 📅 Sprint 4 : Frontend MVP (À faire)
- [ ] Structure Twig : Layout de base (header, nav, footer, responsive)
- [ ] Dashboard : Page d'accueil avec widgets statistiques
- [ ] Kanban Board : Interface Twig + JS Vanilla (3 colonnes)
- [ ] CRUD Compétences : Formulaires + affichage
- [ ] CRUD Snippets : Liste + formulaires
- [ ] Veille Techno : Liste articles avec liens externes
- [ ] Dark/Light Mode : Toggle CSS + localStorage
- [ ] Responsive Mobile : Media queries Bootstrap

### 📚 Sprint 5 : Documentation & Finitions (À faire)
- [ ] Rédiger le dossier professionnel DWWM
- [ ] Créer le diaporama de présentation
- [ ] Mettre à jour ARCHITECTURE.md
- [ ] Tester déploiement prod Docker
- [ ] Optimisations performances (OPcache, index DB)
- [ ] Validation W3C HTML/CSS
- [ ] Préparation soutenance orale

### 🚀 Post-Certification : Migration Angular (Bonus Future)
- [ ] Setup Angular 18 + routing
- [ ] Migration progressive composants (Dashboard, Kanban, Profil, etc.)
- [ ] Services Angular + Interceptor HTTP
- [ ] Tests Jasmine/Karma
- [ ] Drag & Drop Kanban avec CDK

---

## 🎓 Contexte de certification

Ce projet est réalisé dans le cadre de la certification **Développeur Web et Web Mobile (DWWM)** - Niveau 5 (Bac+2).

### Compétences validées

| Code | Compétence | Validation MVP |
|------|------------|----------------|
| **CP1** | Installer et configurer son environnement de travail | ✅ Docker dev + prod |
| **CP2** | Maquetter des interfaces utilisateur | ✅ Maquettes Figma |
| **CP3** | Réaliser des interfaces utilisateur statiques | ✅ Templates Twig + Bootstrap |
| **CP4** | Développer la partie dynamique des interfaces | ✅ JavaScript Vanilla (Fetch API, DOM) |
| **CP5** | Mettre en place une base de données relationnelle | ✅ PostgreSQL 16 (MCD/MLD/UML) |
| **CP6** | Développer des composants d'accès aux données SQL et NoSQL | ✅ Doctrine ORM + ODM |
| **CP7** | Développer des composants métier côté serveur | ✅ Symfony Services (RSS, Auth) |
| **CP8** | Documenter le déploiement | ✅ README + ARCHITECTURE + Dossier pro |

### Timeline du projet
- **Début** : 8 décembre 2024
- **Fin MVP** : Mi-janvier 2026
- **Présentation** : Février 2026
- **Méthodologie** : Agile (sprints d'1 semaine)

---

## 🎯 Pourquoi cette architecture hybride ?

**MongoDB pour Snippets/Articles :**
- ✅ Flexibilité du schéma (code multi-langages, RSS variables)
- ✅ Arrays natifs pour tags (pas de table de liaison)
- ✅ Performance sur gros volumes
- ✅ Stockage JSON naturel

**PostgreSQL pour User/Project/Task/Competence :**
- ✅ Relations strictes nécessaires (User → Projects → Tasks)
- ✅ CASCADE on delete requis (supprimer user = supprimer projets)
- ✅ Intégrité référentielle critique
- ✅ Transactions ACID pour la cohérence

**Résultat :** Le meilleur des deux mondes pour une application moderne et performante.

---

## 👨‍💻 Auteur

**Anthony CATAN-CAVERY** - Développeur Web et Web Mobile en formation  
🔗 [LinkedIn](https://www.linkedin.com/in/anthonycatancavery)  
🎓 Certification DWWM - Février 2026

---

## 📄 Licence

Projet éducatif - Certification DWWM 2024-2026

---

**⭐ Si ce projet vous inspire pour votre propre certification, n'hésitez pas à mettre une étoile !**