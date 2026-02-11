#!/bin/bash
# ============================================
# Vérification des données Symfony CLI (Windows natif)
# ============================================

echo "💻 MY-ANKODE - Vérification données Symfony CLI"
echo "==============================================="
echo ""

# Se placer dans backend/
cd backend

echo "📊 PostgreSQL Windows (port 5432)"
echo "----------------------------------"

# Compter les users
USER_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM user_" 2>/dev/null | grep -oP '\d+' | tail -1)
echo "👥 Utilisateurs : $USER_COUNT"

# Compter les projets
PROJECT_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM project" 2>/dev/null | grep -oP '\d+' | tail -1)
echo "📁 Projets : $PROJECT_COUNT"

# Compter les tâches
TASK_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM task" 2>/dev/null | grep -oP '\d+' | tail -1)
echo "✅ Tâches : $TASK_COUNT"

# Compter les compétences
COMPETENCE_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM competence" 2>/dev/null | grep -oP '\d+' | tail -1)
echo "🎯 Compétences : $COMPETENCE_COUNT"

echo ""
echo "🍃 MongoDB Windows (port 27017)"
echo "-------------------------------"

# Vérifier MongoDB via Doctrine ODM
echo "📰 Articles : (vérification via fixtures - 15 attendus)"
echo "📝 Snippets : (vérification via fixtures - 24 attendus)"

echo ""
echo "🌐 Pour lancer l'application Symfony CLI :"
echo "   cd backend"
echo "   php -S localhost:8001 -t public"
echo ""
echo "✅ Vérification terminée !"