# MY-ANKODE

[![Documentation](https://img.shields.io/badge/docs-architecture-blue?style=for-the-badge&logo=readthedocs&logoColor=white)](./ARCHITECTURE.md)
[![Symfony](https://img.shields.io/badge/Symfony-7-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![Angular](https://img.shields.io/badge/Angular-18-DD0031?style=for-the-badge&logo=angular&logoColor=white)](https://angular.io/)
[![Docker](https://img.shields.io/badge/Docker-Dev%20%2B%20Prod-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![MongoDB](https://img.shields.io/badge/MongoDB-6-47A248?style=for-the-badge&logo=mongodb&logoColor=white)](https://www.mongodb.com/)
[![License](https://img.shields.io/badge/license-Educational-green?style=for-the-badge)](./LICENSE)

> Application web complète pour jeunes développeurs - Projet de certification DWWM (Développeur Web et Web Mobile)

---

## 📋 Description

MY-ANKODE est une application tout-en-un destinée aux développeurs juniors, proposant :

- 📊 **Gestion de projets** : Kanban pour organiser vos tâches (À faire / En cours / Terminé)
- 💾 **Bibliothèque de code** : Snippets avec annotations et explications
- 📰 **Veille technologique** : Agrégation de flux RSS (Dev.to, Medium, etc.)
- 🎯 **Suivi de compétences** : Profil développeur et auto-évaluation

---

## 🛠️ Stack Technique

### Backend
- **Framework** : Symfony 7 (PHP 8.3+)
- **Architecture** : API REST (JSON)
- **Authentification** : Symfony Security + bcrypt
- **Bases de données** : 
  - **PostgreSQL 16** (Données relationnelles : User, Project, Task, Competence)
  - **MongoDB 6** (Données documentaires : Snippet, Article)

### Frontend
- **Framework** : Angular 18 (TypeScript)
- **UI Library** : Bootstrap 5 + ng-bootstrap
- **Styling** : SCSS personnalisé

### DevOps
- **Conteneurisation** : Docker + Docker Compose
- **Environnements** : Dev (php -S) + Prod (Nginx + PHP-FPM)
- **Versioning** : Git + GitHub (Git Flow)

---

## 🗄️ Architecture Hybride PostgreSQL + MongoDB

MY-ANKODE utilise une **architecture de données hybride** pour tirer parti des forces de chaque technologie.

### PostgreSQL (Relationnel)
**Usage :** Données structurées nécessitant une intégrité référentielle stricte

- 👤 **USER** : Utilisateurs et authentification
- 📁 **PROJECT** : Projets utilisateur (1 user → N projects)
- ✅ **TASK** : Tâches en mode Kanban (1 project → N tasks)
- 🎯 **COMPETENCE** : Compétences et portfolio

**Avantages :**
- Relations strictes avec CASCADE
- Intégrité référentielle garantie
- Transactions ACID

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

### Justification
Pour une analyse détaillée de ce choix architectural, consultez [DECISIONS.md](./DECISIONS.md)

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| **[ARCHITECTURE.md](./ARCHITECTURE.md)** | Architecture 3-tiers, modules Symfony, endpoints API |
| **[DECISIONS.md](./DECISIONS.md)** | Justification architecture hybride PostgreSQL + MongoDB |
| **[backend/README.md](./backend/README.md)** | Documentation technique backend (Entities, Documents, Routes) |
| **[`/docs/schemas/`](./docs/schemas/)** | Diagrammes UML et Merise (MCD, MLD, MPD) |
| **[`/docs/maquettes/`](./docs/maquettes/)** | Maquettes Figma des interfaces utilisateur |

📖 **Pour comprendre l'organisation du code**, consultez [ARCHITECTURE.md](./ARCHITECTURE.md)

---

## 🚀 Installation

### Prérequis
- Docker Desktop (recommandé)
- OU : PHP 8.3+, Composer, PostgreSQL 16, MongoDB 6, Node.js 20+, Angular CLI 18

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

#### Frontend (Angular)
```bash
cd frontend/my-ankode-app
npm install
ng serve
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
- ✅ Volumes montés (code modifiable depuis Windows)
- ✅ Debug activé (APP_DEBUG=1)
- ✅ Logs verbeux pour débogage

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
- **Frontend** : http://localhost:4200 *(à venir)*
- **PostgreSQL** : localhost:5432
- **MongoDB** : localhost:27017

### Production (`docker-compose.prod.yml`)
- **Application** : http://localhost
- **Page auth** : http://localhost/auth
- **PostgreSQL** : localhost:5432 *(conteneur interne)*
- **MongoDB** : localhost:27017 *(conteneur interne)*

---

## 🗂️ Structure du projet
```
my-ankode/
├── backend/                   # API Symfony
│   ├── src/
│   │   ├── Command/           # Commandes console (test MongoDB, etc.)
│   │   ├── Controller/        # Controllers API REST
│   │   ├── Entity/            # Entities Doctrine (PostgreSQL)
│   │   ├── Document/          # Documents MongoDB ODM
│   │   ├── Repository/        # Repositories
│   │   └── Security/          # Authentification
│   ├── config/
│   │   └── packages/
│   │       ├── doctrine.yaml           # Config PostgreSQL
│   │       ├── doctrine_mongodb.yaml   # Config MongoDB
│   │       └── security.yaml           # Config sécurité
│   ├── migrations/            # Migrations PostgreSQL
│   ├── Dockerfile             # Image Docker dev
│   ├── Dockerfile.prod        # Image Docker production
│   └── README.md              # Documentation backend
├── frontend/                  # Application Angular
│   └── my-ankode-app/
│       └── src/
│           └── app/           # Composants Angular
├── nginx/                     # Configuration Nginx production
│   └── default.conf           # Routing Symfony + sécurité
├── docs/                      # Documentation
│   ├── schemas/               # Diagrammes UML/Merise
│   └── maquettes/             # Maquettes Figma
├── docker-compose.yml         # Environnement DEV
├── docker-compose.prod.yml    # Environnement PROD
├── ARCHITECTURE.md            # Architecture technique
├── DECISIONS.md               # Décisions architecturales
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

### Tests Backend (PHPUnit)
```bash
cd backend
php bin/phpunit
```

### Tests Frontend (Jasmine/Karma)
```bash
cd frontend/my-ankode-app
ng test
```

---

## 🛣️ Roadmap

### Sprint 1 : Architecture & Auth (Terminé ✅)
- [x] Setup environnement (Symfony + Angular)
- [x] Configuration bases de données (PostgreSQL + MongoDB)
- [x] Modélisation UML et Merise
- [x] Déploiement Docker (dev + prod)
- [x] Authentification Backend (User entity + Security)
- [x] Authentification Frontend (Interface /auth)
- [x] Entities Project & Task (PostgreSQL)
- [x] Configuration MongoDB + Documents (Snippet, Article)

### Sprint 2 : API REST & CRUD (En cours)
- [ ] API REST CRUD Projects
- [ ] API REST CRUD Tasks
- [ ] CRUD Snippets (MongoDB)
- [ ] Module Veille (Flux RSS → Articles)

### Sprint 3 : Frontend Angular
- [ ] Composants Angular (Dashboard, Kanban)
- [ ] Services et routing Angular
- [ ] Intégration API REST

### Sprint 4 : Fonctionnalités avancées
- [ ] Module Compétences
- [ ] Drag & Drop Kanban
- [ ] Filtres et recherche

### Sprint 5 : Finitions & Tests
- [ ] Tests unitaires (PHPUnit + Jasmine)
- [ ] Optimisations performances
- [ ] Documentation finale
- [ ] Préparation certification

---

## 🎓 Contexte de certification

Ce projet est réalisé dans le cadre de la certification **Développeur Web et Web Mobile (DWWM)** - Niveau 5 (Bac+2).

### Compétences validées

| Code | Compétence | Validation |
|------|------------|------------|
| **CP1** | Installer et configurer son environnement de travail | Docker dev + prod ✅ |
| **CP2** | Maquetter des interfaces utilisateur | Maquettes Figma ✅ |
| **CP3** | Réaliser des interfaces utilisateur statiques | Templates Twig ✅ |
| **CP4** | Développer la partie dynamique des interfaces | Angular 18 (en cours) |
| **CP5** | Mettre en place une base de données relationnelle | PostgreSQL 16 ✅ |
| **CP6** | Développer des composants d'accès aux données SQL et NoSQL | Doctrine ORM + ODM ✅ |
| **CP7** | Développer des composants métier côté serveur | Symfony Services (en cours) |
| **CP8** | Documenter le déploiement | README + ARCHITECTURE ✅ |

### Timeline du projet
- **Début** : 8 décembre 2024
- **Fin prévue** : Mi-janvier 2025
- **Présentation** : Février 2026
- **Méthodologie** : Agile (sprints d'1 semaine)

---

## 🎯 Pourquoi cette architecture hybride ?

**MongoDB pour Snippets/Articles :**
- ✅ Flexibilité du schéma (code multi-langages, RSS variables)
- ✅ Arrays natifs pour tags (pas de table de liaison)
- ✅ Performance sur gros volumes
- ✅ Stockage JSON naturel

**PostgreSQL pour User/Project/Task :**
- ✅ Relations strictes nécessaires (User → Projects → Tasks)
- ✅ CASCADE on delete requis (supprimer user = supprimer projets)
- ✅ Intégrité référentielle critique
- ✅ Transactions ACID pour la cohérence

**Résultat :** Le meilleur des deux mondes pour une application moderne et performante.

---

## 👨‍💻 Auteur

**Anthony** - Développeur Web et Web Mobile en formation  
🔗 [LinkedIn](https://www.linkedin.com/in/anthonycatancavery)

---

## 📄 Licence

Projet éducatif - Certification DWWM 2024-2025

---

**⭐ Si ce projet vous inspire pour votre propre certification, n'hésitez pas à mettre une étoile !**