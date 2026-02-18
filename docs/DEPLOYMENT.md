# Déploiement

Guide d'installation et de déploiement de MY-ANKODE avec Docker.

[← Retour au README principal](../README.md)

---

## Table des matières

- [Prérequis](#prérequis)
- [Installation](#installation)
- [Architecture Docker](#architecture-docker)
- [Services disponibles](#services-disponibles)
- [Configuration](#configuration)
- [Commandes utiles](#commandes-utiles)
- [Troubleshooting](#troubleshooting)

---

## Prérequis

### Logiciels requis

- **Docker Desktop** 24.x ou supérieur
- **Git** 2.x ou supérieur
- **4 GB RAM minimum** pour les conteneurs

### Systèmes supportés

- ✅ Windows 10/11 (avec WSL2)
- ✅ macOS 12+
- ✅ Linux (Ubuntu 20.04+, Debian 11+)

### Vérification

```bash
# Vérifier Docker
docker --version
docker compose version

# Vérifier Git
git --version
```

---

## Installation

### Étape 1 : Cloner le projet

```bash
git clone https://github.com/[votre-username]/my-ankode.git
cd my-ankode
```

### Étape 2 : Configuration de l'environnement

```bash
# Copier le fichier d'exemple
cp backend/.env.example backend/.env

# Éditer si nécessaire (optionnel pour le développement local)
code backend/.env
```

**⚠️ IMPORTANT - Sécurité** :

Les valeurs ci-dessous sont des **exemples génériques** pour le développement local. En production ou pour un usage réel, vous DEVEZ :
- ✅ Générer des mots de passe forts et uniques
- ✅ Ne JAMAIS versionner le fichier `.env` (déjà dans `.gitignore`)
- ✅ Utiliser `openssl rand -base64 32` pour générer des secrets sécurisés
- ✅ Changer tous les mots de passe par défaut

**Variables principales** (valeurs d'exemple - à personnaliser) :

```env
APP_ENV=dev
DATABASE_URL=postgresql://myankode:myankode@postgres:5432/myankode?serverVersion=16
MONGODB_URL=mongodb://root:root@mongo:27017
MONGODB_DB=myankode
```

### Étape 3 : Lancer les conteneurs

```bash
# Démarrer tous les services
docker compose up -d

# Vérifier que tous les conteneurs sont actifs
docker compose ps
```

**Résultat attendu** :
```
NAME                     STATUS              PORTS
my-ankode-backend        Up 30 seconds       0.0.0.0:8000->8000/tcp
my-ankode-postgres       Up 30 seconds       0.0.0.0:5433->5432/tcp
my-ankode-mongo          Up 30 seconds       0.0.0.0:27018->27017/tcp
my-ankode-pgadmin        Up 30 seconds       0.0.0.0:5050->80/tcp
my-ankode-mongo-express  Up 30 seconds       0.0.0.0:8081->8081/tcp
```

### Étape 4 : Initialiser la base de données

```bash
# Créer le schéma PostgreSQL
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate -n

# Créer les collections MongoDB
docker compose exec php php bin/console doctrine:mongodb:schema:create

# Charger les données de démonstration
docker compose exec php php bin/console doctrine:fixtures:load -n
```

**OU utiliser le script automatique** :

```bash
./scripts/reset-all-fixtures-docker.sh
```

### Étape 5 : Accéder à l'application

Ouvrir dans le navigateur : **http://localhost:8000**

**Compte de test** :
- Email : `anthony@myankode.com`
- Mot de passe : `password123`

---

## Architecture Docker

### Vue d'ensemble

MY-ANKODE utilise Docker Compose pour orchestrer 5 services :

```
┌─────────────────────────────────────────────────────────┐
│                    DOCKER HOST                          │
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   Backend    │  │  PostgreSQL  │  │   MongoDB    │ │
│  │  PHP 8.3     │◄─┤    16        │  │      6       │ │
│  │  Symfony 7   │  │              │  │              │ │
│  │  Port 8000   │  │  Port 5433   │  │  Port 27018  │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│         │                  │                  │        │
│         │          ┌───────┴────────┐        │        │
│         │          │                │        │        │
│  ┌──────┴───────┐  │  ┌──────────────┴────┐  │        │
│  │   pgAdmin    │  │  │  Mongo Express    │  │        │
│  │  Port 5050   │  │  │  Port 8081        │  │        │
│  └──────────────┘  │  └───────────────────┘  │        │
│                    │                          │        │
└────────────────────┼──────────────────────────┼────────┘
                     │                          │
              my-ankode-network (bridge)
```

### Services

| Service | Image | Port | Rôle |
|---------|-------|------|------|
| **backend** | Custom (PHP 8.3-FPM) | 8000 | Application Symfony |
| **postgres** | postgres:16-alpine | 5433 | Base de données relationnelle |
| **mongo** | mongo:6 | 27018 | Base de données documents |
| **pgadmin** | dpage/pgadmin4 | 5050 | Interface PostgreSQL |
| **mongo-express** | mongo-express | 8081 | Interface MongoDB |

**Note** : Les ports externes (5433, 27018) sont décalés pour éviter les conflits avec d'éventuelles installations locales.

---

## Services disponibles

### Application principale

**URL** : http://localhost:8000

Accès à MY-ANKODE avec interface complète :
- Authentification
- Dashboard
- Veille RSS
- Kanban
- Snippets
- Compétences

---

### pgAdmin (Administration PostgreSQL)

**URL** : http://localhost:5050

**Connexion** :
- Email : `admin@myankode.com`
- Mot de passe : `admin`

**Ajouter le serveur PostgreSQL** :
1. Clic droit sur "Servers" → "Register" → "Server"
2. **General** :
   - Name : `MY-ANKODE`
3. **Connection** :
   - Host : `postgres` (nom du service Docker)
   - Port : `5432` (port interne)
   - Database : `myankode`
   - Username : `myankode`
   - Password : `myankode`

---

### Mongo Express (Administration MongoDB)

**URL** : http://localhost:8081

**Connexion** :
- Username : `admin`
- Password : `admin`

Accès direct aux collections :
- `snippets` : Morceaux de code
- `articles` : Articles RSS

---

## Configuration

### Variables d'environnement

MY-ANKODE utilise deux fichiers `.env` :

**⚠️ Note importante** : Les valeurs ci-dessous sont des **exemples génériques**. Personnalisez-les pour votre environnement.

#### 1. `.env` (racine du projet)

Variables pour Docker Compose :

```env
# PostgreSQL (exemples - à personnaliser)
POSTGRES_DB=myankode
POSTGRES_USER=myankode
POSTGRES_PASSWORD=myankode
POSTGRES_PORT=5433

# MongoDB (exemples - à personnaliser)
MONGO_INITDB_ROOT_USERNAME=root
MONGO_INITDB_ROOT_PASSWORD=root
MONGO_PORT=27018

# pgAdmin (exemples - à personnaliser)
PGADMIN_DEFAULT_EMAIL=admin@myankode.com
PGADMIN_DEFAULT_PASSWORD=admin
PGADMIN_PORT=5050

# Mongo Express (exemples - à personnaliser)
ME_CONFIG_MONGODB_ADMINUSERNAME=root
ME_CONFIG_MONGODB_ADMINPASSWORD=root
ME_CONFIG_PORT=8081
```

#### 2. `backend/.env` (application Symfony)

Variables pour l'application :

```env
APP_ENV=dev
APP_SECRET=changeme_in_production

# URLs internes (adaptez les credentials selon votre .env racine)
DATABASE_URL=postgresql://myankode:myankode@postgres:5432/myankode
MONGODB_URL=mongodb://root:root@mongo:27017
MONGODB_DB=myankode
```

### Ports personnalisés

Pour changer les ports exposés, éditer `.env` :

```env
# Exemple : PostgreSQL sur port 5434 au lieu de 5433
POSTGRES_PORT=5434
```

Puis redémarrer :

```bash
docker compose down
docker compose up -d
```

---

## Commandes utiles

### Gestion des conteneurs

```bash
# Démarrer les services
docker compose up -d

# Arrêter les services
docker compose down

# Redémarrer un service spécifique
docker compose restart backend

# Voir les logs
docker compose logs -f backend

# Voir les logs de tous les services
docker compose logs -f

# Voir le statut des conteneurs
docker compose ps
```

### Accès aux conteneurs

```bash
# Shell dans le conteneur backend
docker compose exec php bash

# Shell dans PostgreSQL
docker compose exec postgres psql -U myankode -d myankode

# Shell dans MongoDB
docker compose exec mongo mongosh -u root -p root
```

### Base de données

```bash
# Reset complet (schéma + fixtures)
./scripts/reset-all-fixtures-docker.sh

# Migrations
docker compose exec php php bin/console doctrine:migrations:migrate

# Vider et recréer le schéma
docker compose exec php php bin/console doctrine:schema:drop --force --full-database
docker compose exec php php bin/console doctrine:schema:create
```

### Tests

```bash
# Lancer tous les tests
docker compose exec php php bin/phpunit

# Tests avec statistiques
./scripts/check-tests-docker.sh

# Tests spécifiques
docker compose exec php php bin/phpunit tests/Controller/
```

### Maintenance

```bash
# Nettoyer le cache Symfony
docker compose exec php php bin/console cache:clear

# Vérifier les dépendances
docker compose exec php composer validate

# Mettre à jour les dépendances
docker compose exec php composer update

# Rebuild complet (après modification Dockerfile)
docker compose build --no-cache
docker compose up -d
```

---

## Troubleshooting

### Problème : Les conteneurs ne démarrent pas

**Symptôme** : `docker compose up -d` échoue

**Solutions** :

```bash
# 1. Vérifier les logs
docker compose logs

# 2. Vérifier que Docker est lancé
docker ps

# 3. Libérer les ports occupés
# Windows
netstat -ano | findstr :8000
netstat -ano | findstr :5433

# Linux/Mac
lsof -i :8000
lsof -i :5433

# 4. Nettoyer et redémarrer
docker compose down -v
docker compose up -d
```

---

### Problème : Erreur de connexion PostgreSQL

**Symptôme** : `Connection refused` ou `could not connect to server`

**Solutions** :

```bash
# 1. Vérifier que PostgreSQL est prêt
docker compose logs postgres

# 2. Tester la connexion
docker compose exec postgres pg_isready -U myankode

# 3. Vérifier les credentials dans backend/.env
cat backend/.env | grep DATABASE_URL

# 4. Recréer le conteneur
docker compose down
docker volume rm my-ankode_postgres-data
docker compose up -d
```

---

### Problème : MongoDB authentification failed

**Symptôme** : `Authentication failed`

**Solutions** :

```bash
# 1. Vérifier les credentials
docker compose exec mongo mongosh -u root -p root --authenticationDatabase admin

# 2. Recréer MongoDB
docker compose down
docker volume rm my-ankode_mongo-data
docker compose up -d mongo
```

---

### Problème : Port déjà utilisé

**Symptôme** : `Bind for 0.0.0.0:8000 failed: port is already allocated`

**Solutions** :

```bash
# 1. Identifier le processus
# Windows
netstat -ano | findstr :8000
taskkill /PID <PID> /F

# Linux/Mac
lsof -ti:8000 | xargs kill -9

# 2. OU changer le port dans .env
BACKEND_PORT=8001
```

---

### Problème : Fixtures ne se chargent pas

**Symptôme** : Base de données vide après `doctrine:fixtures:load`

**Solutions** :

```bash
# 1. Vérifier l'ordre de chargement
docker compose exec php php bin/console doctrine:fixtures:load --group=user

# 2. Utiliser le script complet
./scripts/reset-all-fixtures-docker.sh

# 3. Vérifier les données
docker compose exec php php bin/console doctrine:query:sql 'SELECT COUNT(*) FROM "user_"'
```

---

### Problème : Composer install très lent

**Symptôme** : `composer install` prend plusieurs minutes

**Solutions** :

```bash
# 1. Utiliser le cache Composer
docker compose exec php composer install --prefer-dist

# 2. Désactiver Xdebug temporairement
docker compose exec php php -d xdebug.mode=off /usr/bin/composer install

# 3. Augmenter la mémoire
docker compose exec php php -d memory_limit=2G /usr/bin/composer install
```

---

### Problème : Permission denied sur fichiers

**Symptôme** : `Permission denied` lors de l'écriture de fichiers

**Solutions** :

```bash
# Linux/Mac : Fixer les permissions
sudo chown -R $USER:$USER backend/var
sudo chmod -R 775 backend/var

# Ou dans le conteneur
docker compose exec php chown -R www-data:www-data /var/www/html/var
```

---

## Nettoyage complet

Pour supprimer complètement l'environnement :

```bash
# Arrêter et supprimer les conteneurs + volumes
docker compose down -v

# Supprimer les images
docker compose down --rmi all

# Nettoyer Docker complètement (attention : supprime TOUT)
docker system prune -a --volumes
```

---

## Déploiement en production

### Checklist

- [ ] Changer `APP_ENV=prod` dans `backend/.env`
- [ ] Générer un `APP_SECRET` sécurisé
- [ ] Changer tous les mots de passe par défaut
- [ ] Configurer HTTPS
- [ ] Désactiver pgAdmin et Mongo Express
- [ ] Activer les logs de production
- [ ] Configurer des sauvegardes automatiques
- [ ] Limiter les ressources des conteneurs

### Variables de production

```env
APP_ENV=prod
APP_SECRET=[généré avec: php bin/console secrets:generate-key]
DATABASE_URL=postgresql://secure_user:strong_password@postgres:5432/myankode
MONGODB_URL=mongodb://secure_user:strong_password@mongo:27017
```

---

[← Retour au README principal](../README.md)
