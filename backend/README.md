# MY-ANKODE

[![Symfony](https://img.shields.io/badge/Symfony-7-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![Angular](https://img.shields.io/badge/Angular-18-DD0031?style=for-the-badge&logo=angular&logoColor=white)](https://angular.io/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![MongoDB](https://img.shields.io/badge/MongoDB-6-47A248?style=for-the-badge&logo=mongodb&logoColor=white)](https://www.mongodb.com/)

> Plateforme de productivité pour développeurs juniors - Projet de certification DWWM

---

## 📋 Fonctionnalités

- 📊 **Kanban** - Gestion de projets et tâches (À faire / En cours / Terminé)
- 💾 **Snippets** - Bibliothèque de code avec annotations et tags
- 📰 **Veille techno** - Agrégation de flux RSS (Dev.to, Korben, etc.)
- 🎯 **Compétences** - Suivi de progression développeur (à venir)

---

## 🛠️ Stack Technique

**Backend** : Symfony 7 (PHP 8.3) + API REST  
**Frontend** : JavaScript Vanilla ES6+ (MVP) → Angular 18 (migration en cours)  
**Bases de données** : PostgreSQL 16 (relationnel) + MongoDB 6 (documentaire)  
**DevOps** : Docker Compose (dev + prod)

---

## 🚀 Installation Rapide
```bash
# 1. Cloner le projet
git clone https://github.com/ton-username/my-ankode.git
cd my-ankode

# 2. Démarrer Docker
docker-compose up -d

# 3. Setup Backend
docker-compose exec backend sh
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# 4. Setup MongoDB (schema Snippets & Articles)
php bin/console doctrine:mongodb:schema:create
exit

# 5. Accéder à l'application
# Auth : http://localhost:8000/auth
# Dashboard : http://localhost:8000/dashboard
```

---

## 🗄️ Architecture Hybride

**PostgreSQL** - User, Project, Task (relations strictes, intégrité référentielle)  
**MongoDB** - Snippet, Article (schéma flexible, performance lecture)

---

## 🧪 Tests Rapides

### Authentification
```
http://localhost:8000/auth
→ Inscription / Connexion / Déconnexion
```

### API REST - Projects & Tasks
```bash
# Lister les projets
GET http://localhost:8000/api/projects

# Créer une tâche
POST http://localhost:8000/api/tasks
Body: {"title": "Ma tâche", "projectId": 1, "status": "todo"}
```

### API REST - Snippets (MongoDB)
```bash
# Lister les snippets
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

### Veille RSS
```bash
docker-compose exec backend sh

# Test flux français
php bin/console app:fetch-rss https://korben.info/feed "Korben"

# Test flux anglais
php bin/console app:fetch-rss https://dev.to/feed "Dev.to"

# Vérifier les articles
docker-compose exec mongo mongosh my_ankode --eval "db.articles.countDocuments()"

# Vérifier les snippets
docker-compose exec mongo mongosh my_ankode --eval "db.snippets.countDocuments()"
```

---

## 📚 Documentation Complète

- **[backend/README.md](./backend/README.md)** - API, Entities, Documents, Routes
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - Architecture 3-tiers détaillée
- **[DECISIONS.md](./DECISIONS.md)** - Justification choix techniques

---

## 🛣️ Avancement

### ✅ Sprint 1 & 2 (Terminé)
- Architecture Docker (dev + prod)
- PostgreSQL : User, Project, Task
- MongoDB : Snippet, Article
- Authentification complète
- API REST CRUD Projects & Tasks
- API REST CRUD Snippets (MongoDB)
- Kanban HTML/CSS/JS fonctionnel
- Service RSS + Commande Symfony

### 🔄 Sprint 3 (En cours)
- Migration Angular 18
- Composants & routing
- Intégration API complète

### 📅 Sprint 4 & 5 (Prévu)
- Module Compétences
- Tests unitaires
- Optimisations
- Documentation finale

---

## 🎓 Contexte

Projet de certification **Développeur Web et Web Mobile (DWWM)** - Niveau 5 (Bac+2)  
**Timeline** : Décembre 2024 → Janvier 2025  
**Présentation** : Février 2026  
**Méthodologie** : Agile (sprints 1 semaine)

---

## 👨‍💻 Auteur

**Anthony** - Développeur Web et Web Mobile en formation  
🔗 [LinkedIn](https://www.linkedin.com/in/anthonycatancavery)

---

## 📄 Licence

Projet éducatif - Certification DWWM 2024-2025