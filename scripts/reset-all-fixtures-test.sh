#!/bin/bash

# Script pour charger les fixtures en environnement TEST

echo "=================================================="
echo "RESET FIXTURES - ENVIRONNEMENT TEST"
echo "=================================================="

# 1. Reset PostgreSQL complet
echo ""
echo "Reset schéma PostgreSQL test..."
php bin/console doctrine:schema:drop --force --full-database --env=test
php bin/console doctrine:schema:create --env=test

# 2. Charger Users (PostgreSQL - première fixture)
echo ""
echo "1/4 - Chargement Users..."
php bin/console doctrine:fixtures:load --group=user --no-interaction --env=test

# 3. Charger Projects (PostgreSQL - APPEND)
echo ""
echo "2/4 - Chargement Projects..."
php bin/console doctrine:fixtures:load --group=project --append --no-interaction --env=test

# 4. Charger Tasks (PostgreSQL - APPEND)
echo ""
echo "3/4 - Chargement Tasks..."
php bin/console doctrine:fixtures:load --group=task --append --no-interaction --env=test

# 5. Charger Competences (PostgreSQL - APPEND)
echo ""
echo "4/4 - Chargement Competences..."
php bin/console doctrine:fixtures:load --group=competence --append --no-interaction --env=test

echo ""
echo "=================================================="
echo "FIXTURES TEST CHARGEES AVEC SUCCES !"
echo "=================================================="