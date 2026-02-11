#!/bin/bash
# ============================================
# Vérification des données Docker
# ============================================

echo "🐳 MY-ANKODE - Vérification données Docker"
echo "=========================================="
echo ""

# Vérification que Docker tourne
if ! docker ps | grep -q "my-ankode-backend"; then
    echo "❌ Erreur : Les conteneurs Docker ne sont pas lancés"
    echo "   Lancez d'abord : docker-compose up -d"
    exit 1
fi

echo "📊 PostgreSQL Docker (port 5433)"
echo "--------------------------------"

# Compter les users (table user_ avec underscore)
USER_COUNT=$(docker exec my-ankode-backend php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM user_" 2>/dev/null | grep -oP '\d+' | tail -1)
echo "👥 Utilisateurs : $USER_COUNT"

# Compter les projets
PROJECT_COUNT=$(docker exec my-ankode-backend php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM project" 2>/dev/null | grep -oP '\d+' | tail -1)
echo "📁 Projets : $PROJECT_COUNT"

# Compter les tâches
TASK_COUNT=$(docker exec my-ankode-backend php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM task" 2>/dev/null | grep -oP '\d+' | tail -1)
echo "✅ Tâches : $TASK_COUNT"

# Compter les compétences
COMPETENCE_COUNT=$(docker exec my-ankode-backend php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM competence" 2>/dev/null | grep -oP '\d+' | tail -1)
echo "🎯 Compétences : $COMPETENCE_COUNT"

echo ""
echo "🍃 MongoDB Docker (port 27018)"
echo "------------------------------"

# Récupérer les credentials depuis .env
MONGO_USER=$(grep MONGO_INITDB_ROOT_USERNAME .env | cut -d '=' -f2)
MONGO_PASS=$(grep MONGO_INITDB_ROOT_PASSWORD .env | cut -d '=' -f2)
MONGO_DB=$(grep POSTGRES_DB .env | cut -d '=' -f2)

# Vérifier MongoDB avec authentification
docker exec my-ankode-mongo mongosh --quiet \
  --username "$MONGO_USER" \
  --password "$MONGO_PASS" \
  --authenticationDatabase admin \
  "$MONGO_DB" \
  --eval "
    print('📰 Articles : ' + db.articles.countDocuments());
    print('📝 Snippets : ' + db.snippets.countDocuments());
  "

echo ""
echo "🌐 Accès interfaces web :"
echo "   - Application : http://localhost:8000"
echo "   - pgAdmin     : http://localhost:5050"
echo "   - Mongo Expr  : http://localhost:8081"
echo ""
echo "✅ Vérification terminée !"