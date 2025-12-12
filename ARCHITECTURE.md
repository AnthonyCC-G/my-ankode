# Architecture Applicative - MY-ANKODE

**Projet de certification DWWM**  
**Auteur :** Anthony  
**Date :** Décembre 2024

---

## 1. Présentation du projet

MY-ANKODE est une application web fullstack destinée aux développeurs juniors pour :
- 📋 Gérer leurs projets en mode Kanban (TODO List)
- 💾 Sauvegarder des extraits de code (Snippets)
- 📰 Suivre leur veille technologique (flux RSS)
- 🎯 Tracker leurs compétences

---

## 2. Architecture globale

### Schéma 3-tiers
```
┌──────────────────────────────────────┐
│         FRONTEND (Client)            │
│  Angular 18 + Bootstrap 5            │
│  - Interfaces utilisateur            │
│  - Appels API REST                   │
└─────────────┬────────────────────────┘
              │ HTTP/JSON
              ↓
┌──────────────────────────────────────┐
│         BACKEND (Serveur)            │
│  Symfony 7 (PHP 8.2)                 │
│  - API REST                          │
│  - Logique métier                    │
│  - Authentification                  │
└─────────────┬────────────────────────┘
              │
       ┌──────┴──────┐
       ↓             ↓
┌─────────────┐ ┌─────────────┐
│ PostgreSQL  │ │  MongoDB    │
│             │ │             │
│ - Users     │ │ - Snippets  │
│ - Projects  │ │ - Articles  │
│ - Tasks     │ │             │
│ - Competences│ │             │
└─────────────┘ └─────────────┘
```

**Pourquoi 2 bases de données ?**
- **PostgreSQL** : Données structurées avec relations (User → Projects → Tasks)
- **MongoDB** : Données flexibles (code snippets, articles HTML)

**→ Voir [DECISIONS.md](./docs/DECISIONS.md) pour la justification détaillée.**

---

## 3. Organisation du code Backend

Le code Symfony est organisé en **5 modules fonctionnels** :

### 🔐 Module Security
**Gère :** Authentification et utilisateurs

**Fichiers principaux :**
- `SecurityController.php` : Login, Register, Logout
- `User.php` (Entity) : Données utilisateur
- `UserRepository.php` : Requêtes base de données

**Routes API :**
```
POST /api/register    → Créer un compte
POST /api/login       → Se connecter
POST /api/logout      → Se déconnecter
```

---

### 📋 Module Todo
**Gère :** Projets et tâches Kanban

**Fichiers principaux :**
- `ProjectController.php` : CRUD Projets
- `TaskController.php` : CRUD Tâches
- `Project.php` (Entity)
- `Task.php` (Entity)

**Routes API :**
```
GET    /api/projects              → Liste mes projets
POST   /api/projects              → Créer un projet
DELETE /api/projects/{id}         → Supprimer un projet

GET    /api/projects/{id}/tasks   → Liste tâches d'un projet
POST   /api/projects/{id}/tasks   → Créer une tâche
PATCH  /api/tasks/{id}/status     → Changer statut tâche
DELETE /api/tasks/{id}            → Supprimer une tâche
```

---

### 💾 Module Snippet
**Gère :** Extraits de code

**Fichiers principaux :**
- `SnippetController.php` : CRUD Snippets
- Base de données : MongoDB (collection `snippets`)

**Routes API :**
```
GET    /api/snippets        → Liste mes snippets
POST   /api/snippets        → Créer un snippet
PUT    /api/snippets/{id}   → Modifier un snippet
DELETE /api/snippets/{id}   → Supprimer un snippet
```

**Structure données MongoDB :**
```json
{
  "user_id": "123",
  "title": "Boucle forEach en JS",
  "language": "javascript",
  "code": "array.forEach(item => { ... })",
  "notes": "Utilisé pour parcourir un tableau"
}
```

---

### 📰 Module Veille
**Gère :** Flux RSS et articles

**Fichiers principaux :**
- `VeilleController.php` : Affiche les articles
- `RssFeedService.php` : Récupère les flux RSS
- Base de données : MongoDB (collection `articles`)

**Routes API :**
```
GET /api/articles    → Liste 20 derniers articles
```

---

### 🎯 Module Profile
**Gère :** Compétences du développeur

**Fichiers principaux :**
- `CompetenceController.php` : CRUD Compétences
- `Competence.php` (Entity)

**Routes API :**
```
GET    /api/competences        → Liste mes compétences
POST   /api/competences        → Créer une compétence
PUT    /api/competences/{id}   → Modifier une compétence
DELETE /api/competences/{id}   → Supprimer une compétence
```

---

## 4. Sécurité

### Authentification
- Login avec email + password
- Token JWT généré après connexion
- Token stocké côté Angular (localStorage)
- Token envoyé dans chaque requête API

### Protection des données
- Passwords hashés (bcrypt)
- Validation des entrées (Assert)
- Vérification des droits (un user ne voit que SES données)
- Protection CSRF sur les formulaires

---

## 5. Technologies utilisées

| Technologie | Utilisation |
|-------------|-------------|
| **Symfony 7** | Framework PHP pour l'API |
| **Angular 18** | Framework frontend |
| **Bootstrap 5** | Design responsive |
| **PostgreSQL** | Base relationnelle |
| **MongoDB** | Base documentaire |
| **Docker** | Conteneurisation |

---


**Document créé le :** 12/12/2024  
**Version :** 1.0 - Simplifié