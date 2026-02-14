#!/bin/bash
# ============================================
# Vérification rapide des données - Environnement LOCAL (Symfony CLI)
# 
# Usage: ./scripts/check-data.sh
# Objectif: Diagnostic rapide de l'état des données sans les modifier
# ============================================

echo "MY-ANKODE - Verification donnees Symfony CLI"
echo "============================================="
echo ""

# Se placer dans backend/
cd "$(dirname "$0")/../backend" || exit 1

echo "PostgreSQL Windows (port 5432)"
echo "------------------------------"

# Compter les users (attention: table user_ avec underscore)
USER_COUNT=$(php bin/console doctrine:query:sql 'SELECT COUNT(*) as count FROM "user_"' 2>/dev/null | grep -oP '\d+' | tail -1)
echo "Utilisateurs : ${USER_COUNT:-0}"

# Compter les projets
PROJECT_COUNT=$(php bin/console doctrine:query:sql 'SELECT COUNT(*) as count FROM project' 2>/dev/null | grep -oP '\d+' | tail -1)
echo "Projets      : ${PROJECT_COUNT:-0}"

# Compter les tâches
TASK_COUNT=$(php bin/console doctrine:query:sql 'SELECT COUNT(*) as count FROM task' 2>/dev/null | grep -oP '\d+' | tail -1)
echo "Taches       : ${TASK_COUNT:-0}"

# Compter les compétences
COMPETENCE_COUNT=$(php bin/console doctrine:query:sql 'SELECT COUNT(*) as count FROM competence' 2>/dev/null | grep -oP '\d+' | tail -1)
echo "Competences  : ${COMPETENCE_COUNT:-0}"

echo ""
echo "MongoDB Windows (port 27017)"
echo "----------------------------"

# MongoDB: vérification via commande Doctrine (optionnel)
echo "Articles     : (attendu: 15)"
echo "Snippets     : (attendu: 24)"

echo ""
echo "Attendu apres reset-all-fixtures:"
echo "  - Users: 4, Projects: 15, Tasks: 70, Competences: 22"
echo "  - Articles: 15, Snippets: 24"
echo ""
echo "Pour lancer l'application :"
echo "  cd backend && php -S localhost:8001 -t public"
echo ""
echo "Verification terminee !"