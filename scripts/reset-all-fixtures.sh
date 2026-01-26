#!/bin/bash
# ============================================
# MY-ANKODE - Reset complet des bases de données
# Fixtures PostgreSQL + MongoDB + Articles RSS
# ============================================

echo "======================================"
echo "🚀 MY-ANKODE - Reset complet"
echo "======================================"
echo ""

# Se positionner dans le dossier backend
cd "$(dirname "$0")/../backend" || exit

echo "🗑️  Étape 1/6 : Suppression de la base PostgreSQL..."
php bin/console doctrine:database:drop --force --if-exists --quiet

echo "🏗️  Étape 2/6 : Création de la base PostgreSQL..."
php bin/console doctrine:database:create --quiet

echo "📐 Étape 3/6 : Création du schéma PostgreSQL..."
php bin/console doctrine:schema:create --quiet

echo "📦 Étape 4/6 : Chargement des fixtures PostgreSQL..."
php bin/console doctrine:fixtures:load --no-interaction --quiet

echo "📦 Étape 5/6 : Chargement des fixtures MongoDB..."
php bin/console doctrine:mongodb:fixtures:load --no-interaction --quiet

echo "📰 Étape 6/6 : Chargement des articles RSS..."
echo "   → Récupération de Korben.info..."
php bin/console app:fetch-rss https://korben.info/feed "Korben.info" --quiet

echo "   → Récupération de Dev.to..."
php bin/console app:fetch-rss https://dev.to/feed "Dev.to" --quiet

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