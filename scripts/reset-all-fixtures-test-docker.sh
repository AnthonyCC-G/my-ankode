#!/bin/bash

# Script pour charger les fixtures en environnement TEST (DOCKER)

echo "=================================================="
echo "RESET FIXTURES TEST - DOCKER"
echo "=================================================="

# 0. CRÉER LA BASE DE DONNÉES TEST SI ELLE N'EXISTE PAS
echo ""
echo "Vérification/Création de la base de données test..."

# Utiliser le bon user PostgreSQL : ankode_docker
docker-compose exec -T postgres psql -U ankode_docker -d postgres <<-EOSQL
    DROP DATABASE IF EXISTS my_ankode_docker_test;
    CREATE DATABASE my_ankode_docker_test;
EOSQL

echo " Base de données my_ankode_docker_test créée avec succès"

# 1. Reset PostgreSQL complet (schéma)
echo ""
echo "Reset schéma PostgreSQL test..."
docker-compose exec backend php bin/console doctrine:schema:drop --force --full-database --env=test 2>/dev/null || true
docker-compose exec backend php bin/console doctrine:schema:create --env=test

# 2. Charger Users (PostgreSQL - première fixture)
echo ""
echo "1/4 - Chargement Users..."
docker-compose exec backend php bin/console doctrine:fixtures:load --group=user --no-interaction --env=test

# 3. Charger Projects (PostgreSQL - APPEND)
echo ""
echo "2/4 - Chargement Projects..."
docker-compose exec backend php bin/console doctrine:fixtures:load --group=project --append --no-interaction --env=test

# 4. Charger Tasks (PostgreSQL - APPEND)
echo ""
echo "3/4 - Chargement Tasks..."
docker-compose exec backend php bin/console doctrine:fixtures:load --group=task --append --no-interaction --env=test

# 5. Charger Competences (PostgreSQL - APPEND)
echo ""
echo "4/4 - Chargement Competences..."
docker-compose exec backend php bin/console doctrine:fixtures:load --group=competence --append --no-interaction --env=test

echo ""
echo "=================================================="
echo "FIXTURES TEST CHARGEES AVEC SUCCES !"
echo "=================================================="