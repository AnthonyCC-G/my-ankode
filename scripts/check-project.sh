#!/bin/bash

echo "🔍 MY-ANKODE - Vérification de l'environnement"
echo "=============================================="

# Docker
echo ""
echo "🐳 Docker Desktop :"
docker --version 2>/dev/null && echo "✅ Docker installé" || echo "❌ Docker non trouvé"

echo ""
echo "📦 Conteneurs actifs :"
docker-compose ps

# PHP & Composer
echo ""
echo "🐘 PHP :"
docker-compose exec backend php --version 2>/dev/null && echo "✅ PHP fonctionnel" || echo "❌ Conteneur backend non démarré"

echo ""
echo "📦 Composer :"
docker-compose exec backend composer --version 2>/dev/null && echo "✅ Composer fonctionnel" || echo "❌ Composer non accessible"

# PostgreSQL
echo ""
echo "🐘 PostgreSQL :"
if docker-compose ps postgres | grep -q "Up"; then
    docker-compose exec -T backend sh -c 'php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1'
    if [ $? -eq 0 ]; then
        echo "✅ PostgreSQL connecté et accessible depuis Symfony"
        PG_VERSION=$(docker-compose exec -T postgres psql -U ankode_user -d my_ankode -tAc "SELECT version();" 2>/dev/null | head -1)
        if [ ! -z "$PG_VERSION" ]; then
            echo "   Version : $(echo $PG_VERSION | cut -d',' -f1)"
        fi
    else
        echo "⚠️ PostgreSQL tourne mais connexion Symfony échoue"
    fi
else
    echo "❌ Conteneur PostgreSQL arrêté"
fi

# MongoDB
echo ""
echo "🍃 MongoDB :"
if docker-compose ps mongo | grep -q "Up"; then
    MONGO_VERSION=$(docker-compose exec mongo mongosh my_ankode --quiet --eval "db.version()" 2>/dev/null | tr -d '\r')
    if [ ! -z "$MONGO_VERSION" ]; then
        echo "✅ MongoDB $MONGO_VERSION connecté"
    else
        echo "⚠️ MongoDB tourne mais non accessible"
    fi
else
    echo "❌ Conteneur MongoDB arrêté"
fi

# Git
echo ""
echo "🐙 Git :"
git --version && echo "✅ Git installé" || echo "❌ Git non trouvé"
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null)
if [ ! -z "$CURRENT_BRANCH" ]; then
    echo "📍 Branche actuelle : $CURRENT_BRANCH"
fi

# Node.js
echo ""
echo "🟢 Node.js :"
NODE_VERSION=$(node --version 2>/dev/null)
if [ ! -z "$NODE_VERSION" ]; then
    echo "✅ Node.js $NODE_VERSION installé"
else
    echo "⚠️ Node.js non trouvé"
fi

# Angular CLI - VERSION CORRIGÉE (retour à la version qui fonctionnait)
echo ""
echo "🅰️ Angular CLI :"
# Rediriger stderr pour éviter le logo ASCII en doublon, mais garder la détection simple
NG_VERSION=$(ng version 2>&1 | grep "Angular CLI" | head -1)
if [ ! -z "$NG_VERSION" ]; then
    echo "✅ Angular CLI installé"
    echo "   $(echo $NG_VERSION | awk '{print $3}')"
else
    echo "⚠️ Angular CLI non installé"
fi

# Symfony
echo ""
echo "🎼 Symfony :"
SYMFONY_VERSION=$(docker-compose exec -T backend php bin/console --version 2>/dev/null | head -1)
if [ ! -z "$SYMFONY_VERSION" ]; then
    echo "✅ $SYMFONY_VERSION"
else
    echo "⚠️ Symfony non accessible"
fi

# Résumé des URLs
echo ""
echo "=============================================="
echo "🌐 URLs de l'application :"
echo "   Backend  : http://localhost:8000"
echo "   Auth     : http://localhost:8000/auth"
echo "   Kanban   : http://localhost:8000/kanban.html"
echo "   Dashboard: http://localhost:8000/dashboard"
echo "   API      : http://localhost:8000/api/projects"
echo ""
echo "✅ Vérification terminée !"
