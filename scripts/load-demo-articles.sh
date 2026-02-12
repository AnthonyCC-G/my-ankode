#!/bin/bash
# ============================================
# Script pour charger des articles RSS réels dans MongoDB Docker
# Usage: bash scripts/load-demo-articles.sh
# ============================================

echo "=========================================="
echo "  CHARGEMENT ARTICLES RSS RÉELS"
echo "=========================================="
echo ""

# Vérification que Docker est lancé
if ! docker ps | grep -q "my-ankode-backend"; then
    echo " Erreur : Les conteneurs Docker ne sont pas lancés"
    echo "   Lance d'abord : docker-compose up -d"
    exit 1
fi

echo " Import des flux RSS..."
echo ""

# Flux francophones tech
echo "1/6 - Korben (actualités tech FR)..."
docker exec my-ankode-backend php bin/console app:fetch-rss https://korben.info/feed "Korben"

echo ""
echo "2/6 - Numerama..."
docker exec my-ankode-backend php bin/console app:fetch-rss https://www.numerama.com/feed/ "Numerama"

echo ""
echo "3/6 - Frandroid (tech grand public)..."
docker exec my-ankode-backend php bin/console app:fetch-rss https://www.frandroid.com/feed "Frandroid"

# Flux anglophones dev
echo ""
echo "4/6 - Dev.to (articles dev communautaires)..."
docker exec my-ankode-backend php bin/console app:fetch-rss https://dev.to/feed "Dev.to"

echo ""
echo "5/6 - FreeCodeCamp (tutoriels dev)..."
docker exec my-ankode-backend php bin/console app:fetch-rss https://www.freecodecamp.org/news/rss "FreeCodeCamp"

echo ""
echo "6/6 - CSS-Tricks (astuces CSS/Frontend)..."
docker exec my-ankode-backend php bin/console app:fetch-rss https://css-tricks.com/feed "CSS-Tricks"

echo ""
echo "=========================================="
echo "  VÉRIFICATION"
echo "=========================================="
echo ""

# Récupérer les credentials depuis .env
MONGO_USER=$(grep MONGO_INITDB_ROOT_USERNAME .env | cut -d '=' -f2)
MONGO_PASS=$(grep MONGO_INITDB_ROOT_PASSWORD .env | cut -d '=' -f2)

# Compter le nombre d'articles en base
echo " Nombre total d'articles dans MongoDB :"
docker exec my-ankode-mongo mongosh \
  --username "$MONGO_USER" \
  --password "$MONGO_PASS" \
  --authenticationDatabase admin \
  my_ankode_docker \
  --quiet \
  --eval "db.articles.countDocuments()"

echo ""
echo " Chargement terminé !"
echo ""
echo " Tu peux maintenant :"
echo "   - Accéder à http://localhost:8000/veille pour voir les articles"
echo "   - Tester l'API : curl http://localhost:8000/api/articles"
echo ""