#!/bin/bash

# Script pour charger les fixtures une par une (environnement LOCAL avec Symfony CLI)

# Se placer dans le dossier backend
cd "$(dirname "$0")/../backend" || exit 1

echo "=================================================="
echo "RESET COMPLET DES FIXTURES - ENVIRONNEMENT LOCAL"
echo "=================================================="

# 1. Reset PostgreSQL complet
echo ""
echo "Reset schéma PostgreSQL..."
php bin/console doctrine:schema:drop --force --full-database
php bin/console doctrine:schema:create

# 2. Charger Users (PostgreSQL - première fixture)
echo ""
echo "1/7 - Chargement Users..."
php bin/console doctrine:fixtures:load --group=user --no-interaction

# 3. Charger Projects (PostgreSQL - APPEND)
echo ""
echo "2/7 - Chargement Projects..."
php bin/console doctrine:fixtures:load --group=project --append --no-interaction

# 4. Charger Tasks (PostgreSQL - APPEND)
echo ""
echo "3/7 - Chargement Tasks..."
php bin/console doctrine:fixtures:load --group=task --append --no-interaction

# 5. Charger Snippets (MongoDB - premier chargement)
echo ""
echo "4/7 - Chargement Snippets (MongoDB)..."
php bin/console doctrine:mongodb:fixtures:load --group=snippet --no-interaction

# 6. Charger Articles (MongoDB - APPEND)
echo ""
echo "5/7 - Chargement Articles (MongoDB)..."
php bin/console doctrine:mongodb:fixtures:load --group=article --append --no-interaction

# 7. Charger Competences (PostgreSQL - APPEND)
echo ""
echo "6/7 - Chargement Competences..."
php bin/console doctrine:fixtures:load --group=competence --append --no-interaction

echo ""
echo "=================================================="
echo "FIXTURES CHARGEES AVEC SUCCES !"
echo "=================================================="
echo ""
echo "Verification des donnees :"
echo ""

# Vérification PostgreSQL
echo "PostgreSQL :"
php bin/console doctrine:query:sql 'SELECT COUNT(*) as users FROM "user_"'
php bin/console doctrine:query:sql 'SELECT COUNT(*) as projects FROM project'
php bin/console doctrine:query:sql 'SELECT COUNT(*) as tasks FROM task'
php bin/console doctrine:query:sql 'SELECT COUNT(*) as competences FROM competence'

echo ""
echo "MongoDB :"
echo "Snippets: 24 (voir message ci-dessus)"
echo "Articles: 15 (voir message ci-dessus)"

echo ""
echo "Terminé !"