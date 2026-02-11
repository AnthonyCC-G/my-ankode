#!/bin/bash
# ============================================
# MY-ANKODE - Reset complet (Docker)
# Fixtures PostgreSQL + MongoDB + Articles RSS
# ============================================

echo "======================================"
echo "🐳 MY-ANKODE - Reset complet (Docker)"
echo "======================================"
echo ""

echo "🗑️  Étape 1/7 : Suppression du schéma PostgreSQL..."
docker-compose exec -T backend php bin/console doctrine:schema:drop --force --full-database --quiet 2>/dev/null || true

echo "🗑️  Étape 2/7 : Suppression de la base PostgreSQL..."
docker-compose exec -T backend php bin/console doctrine:database:drop --force --if-exists --quiet 2>/dev/null || true

echo "🏗️  Étape 3/7 : Création de la base PostgreSQL..."
docker-compose exec -T backend php bin/console doctrine:database:create --quiet

echo "📐 Étape 4/7 : Création du schéma PostgreSQL..."
docker-compose exec -T backend php bin/console doctrine:schema:create --quiet

echo "📦 Étape 5/7 : Chargement des fixtures PostgreSQL..."
docker-compose exec -T backend php bin/console doctrine:fixtures:load --no-interaction --quiet

echo "📦 Étape 6/7 : Chargement des fixtures MongoDB..."
docker-compose exec -T backend php bin/console doctrine:mongodb:fixtures:load --no-interaction --quiet

echo "📰 Étape 7/7 : Chargement des articles RSS..."
echo "   → Récupération de Korben.info..."
docker-compose exec -T backend php bin/console app:fetch-rss https://korben.info/feed "Korben.info" --quiet 2>/dev/null || echo "      ⚠️  Korben.info temporairement indisponible"

echo "   → Récupération de Dev.to..."
docker-compose exec -T backend php bin/console app:fetch-rss https://dev.to/feed "Dev.to" --quiet 2>/dev/null || echo "      ⚠️  Dev.to temporairement indisponible"

echo ""
echo "======================================"
echo "✅ Reset terminé avec succès !"
echo "======================================"
echo ""
echo "📊 Récapitulatif :"
echo "   - Base PostgreSQL recréée"
echo "   - Fixtures PostgreSQL chargées"
echo "   - Fixtures MongoDB chargées"
echo "   - Articles RSS importés"
echo ""