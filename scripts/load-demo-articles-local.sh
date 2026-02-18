#!/bin/bash

echo "=========================================="
echo "  CHARGEMENT ARTICLES RSS R√âELS (LOCAL)"
echo "=========================================="
echo ""

CONFIG_FILE="scripts/rss-sources.conf"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "‚ùå Erreur : Fichier $CONFIG_FILE introuvable"
    exit 1
fi

echo "Ì≥• Import des flux RSS (configuration: $CONFIG_FILE)"
echo ""

total=$(grep -v "^#" "$CONFIG_FILE" | grep -v "^$" | wc -l)
current=0

while IFS='|' read -r source url; do
    [[ "$source" =~ ^#.*$ ]] && continue
    [[ -z "$source" ]] && continue
    
    ((current++))
    
    echo "[$current/$total] Import de $source..."
    
    # CLEF : < /dev/null emp√™che PHP de consommer le stdin de la boucle
    php backend/bin/console app:fetch-rss "$url" "$source" < /dev/null
    
    echo ""
done < "$CONFIG_FILE"

echo "=========================================="
echo "  V√âRIFICATION"
echo "=========================================="
echo ""

mongosh my_ankode --quiet --eval "db.articles.countDocuments()" | xargs echo "Ì≥ä Nombre total d'articles :"

echo ""
mongosh my_ankode --quiet --eval "db.articles.aggregate([
  { \$group: { _id: '\$source', count: { \$sum: 1 } } },
  { \$sort: { count: -1 } }
]).forEach(doc => print(doc._id + ': ' + doc.count + ' articles'))"

echo ""
echo "‚úÖ CHARGEMENT TERMIN√â !"
echo ""
