# MY-ANKODE

[![Documentation](https://img.shields.io/badge/docs-architecture-blue?style=for-the-badge&logo=readthedocs&logoColor=white)](./ARCHITECTURE.md)
[![Symfony](https://img.shields.io/badge/Symfony-7-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![Angular](https://img.shields.io/badge/Angular-18-DD0031?style=for-the-badge&logo=angular&logoColor=white)](https://angular.io/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![MongoDB](https://img.shields.io/badge/MongoDB-6-47A248?style=for-the-badge&logo=mongodb&logoColor=white)](https://www.mongodb.com/)
[![License](https://img.shields.io/badge/license-Educational-green?style=for-the-badge)](./LICENSE)

> Application web complète pour jeunes développeurs - Projet de certification DWWM (Développeur Web et Web Mobile)

---

## 📋 Description

MY-ANKODE est une application tout-en-un destinée aux développeurs juniors, proposant :

- 📰 **Veille technologique** : Agrégation de flux RSS (Dev.to, Medium, etc.)
- 📊 **Gestion de projets** : Kanban pour organiser vos tâches (À faire / En cours / Terminé)
- 💾 **Bibliothèque de code** : Snippets avec annotations et explications
- 🎯 **Suivi de compétences** : Profil développeur et auto-évaluation

---

## 🛠️ Stack Technique

### Backend
- **Framework** : Symfony 7 (PHP 8.2+)
- **Bases de données** : 
  - PostgreSQL 16 (Données relationnelles)
  - MongoDB 6 (Données documentaires)
- **Architecture** : API REST (JSON)
- **Authentification** : JWT

### Frontend
- **Framework** : Angular 18 (TypeScript)
- **UI Library** : Bootstrap 5 + ng-bootstrap
- **Styling** : SCSS

### DevOps
- **Conteneurisation** : Docker + Docker Compose
- **Versioning** : Git + GitHub
- **Workflow** : Git Flow (main / develop / feature)

---

## 📚 Documentation

Ce projet contient plusieurs documents techniques :

| Document | Description |
|----------|-------------|
| **[ARCHITECTURE.md](./ARCHITECTURE.md)** | Architecture 3-tiers, modules Symfony, endpoints API |
| **[`/docs/schemas/`](./docs/schemas/)** | Diagrammes UML et Merise (MCD, MLD, séquences) |
| **`/docs/maquettes/`** | Maquettes des interfaces utilisateur (Figma) |

📖 **Pour comprendre l'organisation du code**, consultez [ARCHITECTURE.md](./ARCHITECTURE.md)

---

## 🚀 Installation

### Prérequis
- PHP 8.3+
- Composer
- Node.js 20+
- PostgreSQL 16
- MongoDB 6
- Angular CLI 18

---

### Option 1 : Installation avec Docker (RECOMMANDÉ)
```bash
# 1. Cloner le repository
git clone https://github.com/ton-username/my-ankode.git
cd my-ankode

# 2. Lancer les conteneurs
docker-compose up -d

# 3. Installer les dépendances Backend
docker exec -it my-ankode-backend bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
exit

# 4. Installer les dépendances Frontend
cd frontend/my-ankode-app
npm install
```

---

### Option 2 : Installation manuelle

#### Backend (Symfony)
```bash
cd backend
composer install
cp .env .env.local
# Configurer DATABASE_URL et MONGODB_URL dans .env.local
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

## 🌐 URLs de développement

- **Frontend** : http://localhost:4200
- **Backend (API)** : http://127.0.0.1:8000/api
- **PostgreSQL** : localhost:5432
- **MongoDB** : localhost:27017

---

## 📸 Captures d'écran

*(À venir - Section à compléter lors de la phase de développement)*

---

## 🗂️ Structure du projet
```
my-ankode/
├── backend/              # API Symfony
│   ├── src/
│   │   ├── Controller/   # Controllers (API REST)
│   │   ├── Entity/       # Entités Doctrine
│   │   ├── Repository/   # Repositories
│   │   └── Service/      # Services métier
│   └── config/
├── frontend/             # Application Angular
│   └── my-ankode-app/
│       └── src/
│           └── app/      # Composants Angular
├── docs/                 # Documentation
│   ├── schemas/          # Diagrammes UML/Merise
│   └── maquettes/        # Maquettes Figma
├── README.md
└── ARCHITECTURE.md       # Documentation technique
```

---

## 🛠️ Stack Technique

### Backend
- **Framework** : Symfony 7 (PHP 8.2+)
- **Bases de données** : 
  - **PostgreSQL 16** (Données relationnelles)
    - Tables : USER, PROJECT, TASK, COMPETENCE
    - Relations : Foreign Keys natives
  - **MongoDB 6** (Données documentaires)
    - Collections : KEYWORD, ARTICLE, SNIPPET
    - Références logiques vers PostgreSQL
- **Architecture** : API REST (JSON)
- **Authentification** : JWT


## 🗄️ Architecture des Données

MY-ANKODE utilise une **architecture hybride** PostgreSQL + MongoDB.

### PostgreSQL (Relationnel)
Gère les données structurées avec intégrité référentielle :
- 👤 **USER** : Utilisateurs et authentification
- 📁 **PROJECT** : Projets utilisateur
- ✅ **TASK** : Tâches en mode Kanban
- 🎯 **COMPETENCE** : Compétences et portfolio

### MongoDB (Documentaire)
Gère les données flexibles et volumineuses :
- 🔖 **KEYWORD** : Mots-clés de veille technologique
- 📰 **ARTICLE** : Articles agrégés depuis flux RSS
- 💾 **SNIPPET** : Extraits de code avec annotations

### Justification
Voir [DECISIONS.md](./docs/DECISIONS.md) pour la justification détaillée de cette architecture.

## 📚 Documentation

Ce projet contient plusieurs documents techniques :

| Document | Description |
|----------|-------------|
| **[ARCHITECTURE.md](./ARCHITECTURE.md)** | Architecture 3-tiers, modules Symfony, endpoints API |
| **[DECISIONS.md](./docs/DECISIONS.md)** | Justification architecture hybride PostgreSQL + MongoDB |
| **[`/docs/schemas/`](./docs/schemas/)** | Diagrammes UML et Merise (MCD, MLD, MPD) |
| **`/docs/maquettes/`** | Maquettes Figma des interfaces utilisateur |


---

## 🧪 Tests

### Backend (PHPUnit)
```bash
cd backend
php bin/phpunit
```

### Frontend (Jasmine/Karma)
```bash
cd frontend/my-ankode-app
ng test
```

---

## 🛣️ Roadmap

- [x] Setup environnement (Symfony + Angular)
- [x] Configuration bases de données (PostgreSQL + MongoDB)
- [x] Modélisation UML et Merise
- [ ] Authentification (JWT)
- [ ] Module TODO (Projets + Tâches Kanban)
- [ ] Module Snippets
- [ ] Module Veille (Flux RSS)
- [ ] Module Compétences
- [ ] Tests unitaires
- [ ] Déploiement Docker

---

## 👨‍💻 Auteur

**Anthony** - Développeur Web et Web Mobile en formation  
🔗 [LinkedIn](https://www.linkedin.com/in/anthonycatancavery) 

---

## 📅 Timeline du projet

- **Début** : 8 décembre 2024
- **Fin prévue** : Mi-janvier 2025
- **Sprints** : 5 sprints d'une semaine (méthodologie Agile)
- **Certification** : Titre professionnel DWWM - Niveau 5

---

## 🎓 Contexte de certification

Ce projet est réalisé dans le cadre de la certification **Développeur Web et Web Mobile (DWWM)** - Niveau 5.

**Compétences validées :**
- CP1 : Installer et configurer son environnement de travail
- CP2 : Maquetter des interfaces utilisateur
- CP3 : Réaliser des interfaces utilisateur statiques
- CP4 : Développer la partie dynamique des interfaces utilisateur
- CP5 : Mettre en place une base de données relationnelle
- CP6 : Développer des composants d'accès aux données SQL et NoSQL
- CP7 : Développer des composants métier côté serveur
- CP8 : Documenter le déploiement d'une application dynamique

---

## 📄 Licence

Projet éducatif - Certification DWWM 2024-2025

---

**⭐ Si ce projet vous inspire, n'hésitez pas à mettre une étoile !**
```

