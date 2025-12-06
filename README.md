# My-Ankode

Application web pour jeunes développeurs - Projet de certification DWWM (Développeur Web et Web Mobile)

## 📋 Description

My-Ankode est une application complète destinée aux jeunes développeurs, proposant :
- 📰 **Veille technologique** : Agrégation de flux RSS
- 📊 **Gestion de projets** : Kanban pour organiser vos tâches
- 💾 **Bibliothèque de code** : Snippets avec annotations
- 🎯 **Suivi de compétences** : Profil et gestion des compétences

## 🛠️ Stack Technique

### Backend
- **Framework** : Symfony 7
- **Base de données** : PostgreSQL 16
- **Architecture** : API REST

### Frontend
- **Framework** : Angular 18
- **UI Library** : Bootstrap 5 + ng-bootstrap
- **Styling** : SCSS

### DevOps
- **Conteneurisation** : Docker (à venir)
- **Versioning** : Git + GitHub

## 🚀 Installation

### Prérequis
- PHP 8.3+
- Composer
- Node.js 20+
- PostgreSQL 16
- Angular CLI 18

### Backend (Symfony)
```bash
cd backend
composer install
cp .env .env.local
# Configurer DATABASE_URL dans .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve
```

### Frontend (Angular)
```bash
cd frontend/my-ankode-app
npm install
ng serve
```

## 🌐 URLs de développement

- **Backend (API)** : http://127.0.0.1:8000
- **Frontend** : http://localhost:4200

## 👨‍💻 Auteur

Anthony - Projet de certification DWWM 2024-2025

## 📅 Timeline

- **Début** : 8 décembre 2024
- **Fin prévue** : Mi-janvier 2025
- **Sprints** : 5 sprints d'une semaine

## 📄 Licence

Projet éducatif - Certification DWWM