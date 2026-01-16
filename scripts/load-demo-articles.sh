#!/bin/bash

# Script pour charger des articles RSS de demo dans MongoDB
# Usage: bash scripts/load-demo-articles.sh

echo "=========================================="
echo "  CHARGEMENT ARTICLES RSS DE DEMO"
echo "=========================================="
echo ""

# Verification que Docker est lance
if ! docker-compose ps | grep -q "backend"; then
    echo "❌ Erreur : Les conteneurs Docker ne sont pas lances"
    echo "   Lance d'abord : docker-compose up -d"
    exit 1
fi

echo "📡 Import des flux RSS..."
echo ""

# Flux francophones tech
echo "1/6 - Korben (actualites tech FR)..."
docker-compose exec -T backend php bin/console app:fetch-rss https://korben.info/feed "Korben"

echo ""
echo "2/6 - Journal du Net (actualites business tech)..."
docker-compose exec -T backend php bin/console app:fetch-rss https://www.journaldunet.com/rss "JDN"

echo ""
echo "3/6 - Frandroid (tech grand public)..."
docker-compose exec -T backend php bin/console app:fetch-rss https://www.frandroid.com/feed "Frandroid"

# Flux anglophones dev
echo ""
echo "4/6 - Dev.to (articles dev communautaires)..."
docker-compose exec -T backend php bin/console app:fetch-rss https://dev.to/feed "Dev.to"

echo ""
echo "5/6 - FreeCodeCamp (tutoriels dev)..."
docker-compose exec -T backend php bin/console app:fetch-rss https://www.freecodecamp.org/news/rss "FreeCodeCamp"

echo ""
echo "6/6 - CSS-Tricks (astuces CSS/Frontend)..."
docker-compose exec -T backend php bin/console app:fetch-rss https://css-tricks.com/feed "CSS-Tricks"

echo ""
echo "=========================================="
echo "  VERIFICATION"
echo "=========================================="
echo ""

# Compte le nombre d'articles en base
echo "📊 Nombre total d'articles dans MongoDB :"
docker-compose exec -T mongo mongosh my_ankode --quiet --eval "db.articles.countDocuments()"

echo ""
echo "✅ Chargement termine !"
echo ""
echo "💡 Tu peux maintenant :"
echo "   - Acceder a /veille pour voir les articles"
echo "   - Tester l'API : curl http://localhost:8000/api/articles"
echo ""
