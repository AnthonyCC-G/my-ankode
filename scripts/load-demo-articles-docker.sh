#!/bin/bash
# ============================================
# Script pour charger des articles RSS réels dans MongoDB Docker
# Les sources sont définies dans scripts/rss-sources.conf
# Usage: bash scripts/load-demo-articles-docker.sh
# ============================================

echo "=========================================="
echo "  CHARGEMENT ARTICLES RSS RÉELS (DOCKER)"
echo "=========================================="
echo ""

# ============================================
# VÉRIFICATIONS PRÉALABLES
# ============================================

# Vérification que Docker est lancé
if ! docker ps | grep -q "my-ankode-backend"; then
    echo " Erreur : Les conteneurs Docker ne sont pas lancés"
    echo "   Lance d'abord : docker-compose up -d"
    exit 1
fi

# Vérification que le fichier de configuration existe
CONFIG_FILE="scripts/rss-sources.conf"

if [ ! -f "$CONFIG_FILE" ]; then
    echo " Erreur : Fichier $CONFIG_FILE introuvable"
    echo "   Crée-le d'abord avec les sources RSS"
    exit 1
fi

# ============================================
# IMPORT DES SOURCES RSS
# ============================================

echo "Import des flux RSS (configuration: $CONFIG_FILE)"
echo ""

# Compter le nombre de sources (en ignorant commentaires et lignes vides)
total=$(grep -v "^#" "$CONFIG_FILE" | grep -v "^$" | wc -l)
current=0

# Lire chaque ligne du fichier de config
while IFS='|' read -r source url; do
    # Ignorer les commentaires
    [[ "$source" =~ ^#.*$ ]] && continue
    
    # Ignorer les lignes vides
    [[ -z "$source" ]] && continue
    
    # Incrémenter le compteur
    ((current++))
    
    # Afficher la progression
    echo "[$current/$total] Import de $source..."
    
    # Exécuter la commande d'import DOCKER
    docker exec my-ankode-backend php bin/console app:fetch-rss "$url" "$source"
    
    echo ""
done < "$CONFIG_FILE"

# ============================================
# VÉRIFICATION DES RÉSULTATS
# ============================================

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

# Compter les articles par source
echo ""
echo "Répartition par source :"
docker exec my-ankode-mongo mongosh \
  --username "$MONGO_USER" \
  --password "$MONGO_PASS" \
  --authenticationDatabase admin \
  my_ankode_docker \
  --quiet \
  --eval "db.articles.aggregate([
    { \$group: { _id: '\$source', count: { \$sum: 1 } } },
    { \$sort: { count: -1 } }
  ]).forEach(doc => print(doc._id + ': ' + doc.count + ' articles'))"

echo ""
echo "=========================================="
echo " CHARGEMENT TERMINÉ !"
echo "=========================================="
echo ""
echo "Tu peux maintenant :"
echo "   - Accéder à http://localhost:8000/veille"
echo "   - Tester l'API : curl http://localhost:8000/api/articles"
echo "   - Filtrer par source : curl http://localhost:8000/api/articles?source=Korben"
echo ""