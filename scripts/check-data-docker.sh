#!/bin/bash
# ============================================
# Vérification rapide des données - Environnement DOCKER
# 
# Usage: ./scripts/check-data-docker.sh
# Objectif: Diagnostic rapide de l'état des données sans les modifier
# Prérequis: Containers Docker lancés (docker-compose up -d)
# ============================================

echo "MY-ANKODE - Verification donnees Docker"
echo "========================================"
echo ""

# Se placer à la racine (où se trouve docker-compose.yaml)
cd "$(dirname "$0")/.." || exit 1

# Vérification que Docker tourne
if ! docker ps | grep -q "my-ankode-backend"; then
    echo "Erreur : Les conteneurs Docker ne sont pas lances"
    echo "  Lancez d'abord : docker-compose up -d"
    exit 1
fi

echo "PostgreSQL Docker (port 5433)"
echo "-----------------------------"

# Compter les users (attention: table user_ avec underscore)
USER_COUNT=$(docker-compose exec -T backend php bin/console doctrine:query:sql 'SELECT COUNT(*) as count FROM "user_"' 2>/dev/null | grep -oP '\d+' | tail -1)
echo "Utilisateurs : ${USER_COUNT:-0}"

# Compter les projets
PROJECT_COUNT=$(docker-compose exec -T backend php bin/console doctrine:query:sql 'SELECT COUNT(*) as count FROM project' 2>/dev/null | grep -oP '\d+' | tail -1)
echo "Projets      : ${PROJECT_COUNT:-0}"

# Compter les tâches
TASK_COUNT=$(docker-compose exec -T backend php bin/console doctrine:query:sql 'SELECT COUNT(*) as count FROM task' 2>/dev/null | grep -oP '\d+' | tail -1)
echo "Taches       : ${TASK_COUNT:-0}"

# Compter les compétences
COMPETENCE_COUNT=$(docker-compose exec -T backend php bin/console doctrine:query:sql 'SELECT COUNT(*) as count FROM competence' 2>/dev/null | grep -oP '\d+' | tail -1)
echo "Competences  : ${COMPETENCE_COUNT:-0}"

echo ""
echo "MongoDB Docker (port 27018)"
echo "---------------------------"

# MongoDB: Compter via mongosh (nom de collections: article et snippet, pas articles/snippets)
MONGO_USER=$(grep MONGO_INITDB_ROOT_USERNAME backend/.env 2>/dev/null | cut -d '=' -f2)
MONGO_PASS=$(grep MONGO_INITDB_ROOT_PASSWORD backend/.env 2>/dev/null | cut -d '=' -f2)
MONGO_DB=$(grep MONGO_INITDB_DATABASE backend/.env 2>/dev/null | cut -d '=' -f2)

# Si les variables ne sont pas trouvées, utiliser les valeurs par défaut
MONGO_USER=${MONGO_USER:-admin}
MONGO_PASS=${MONGO_PASS:-admin}
MONGO_DB=${MONGO_DB:-my_ankode_db}

# Compter les documents MongoDB
ARTICLE_COUNT=$(docker-compose exec -T mongo mongosh --quiet \
  --username "$MONGO_USER" \
  --password "$MONGO_PASS" \
  --authenticationDatabase admin \
  "$MONGO_DB" \
  --eval "db.article.countDocuments()" 2>/dev/null | tail -1)

SNIPPET_COUNT=$(docker-compose exec -T mongo mongosh --quiet \
  --username "$MONGO_USER" \
  --password "$MONGO_PASS" \
  --authenticationDatabase admin \
  "$MONGO_DB" \
  --eval "db.snippet.countDocuments()" 2>/dev/null | tail -1)

echo "Articles     : ${ARTICLE_COUNT:-?}"
echo "Snippets     : ${SNIPPET_COUNT:-?}"

echo ""
echo "Attendu apres reset-all-fixtures:"
echo "  - Users: 4, Projects: 15, Tasks: 70, Competences: 22"
echo "  - Articles: 15, Snippets: 24"
echo ""
echo "Acces interfaces web :"
echo "  - Application : http://localhost:8000"
echo "  - pgAdmin     : http://localhost:5050"
echo "  - Mongo Expr  : http://localhost:8081"
echo ""
echo "Verification terminee !"