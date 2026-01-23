#!/bin/bash
#
# Script de vérification des tests MY-ANKODE
# 
# Ce script prépare l'environnement de test et lance la suite complète :
# - Vérifie que Docker est actif
# - Nettoie les caches dev et test
# - Recharge les fixtures en environnement test (isolation des données)
# - Lance tous les tests PHPUnit avec statistiques détaillées
#
# Usage : ./scripts/check-tests.sh
# Prérequis : Docker Compose actif
#
# Auteur : Anthony (DWWM 2026)
# Date : Janvier 2026

echo "========================================="
echo "🧪 MY-ANKODE - Vérification des tests"
echo "========================================="

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m'

# 1️⃣ Vérifier Docker
echo ""
echo -e "${YELLOW}1️⃣ Vérification Docker...${NC}"
if docker-compose ps | grep -q "Up"; then
    echo -e "${GREEN}✅ Conteneurs actifs${NC}"
else
    echo -e "${RED}❌ Conteneurs non démarrés${NC}"
    echo "Lancer : docker-compose up -d"
    exit 1
fi

# 2️⃣ Cache clear
echo ""
echo -e "${YELLOW}2️⃣ Nettoyage cache...${NC}"
docker-compose exec -T backend php bin/console cache:clear --quiet
echo -e "${GREEN}✅ Cache dev cleared${NC}"
docker-compose exec -T backend php bin/console cache:clear --env=test --quiet
echo -e "${GREEN}✅ Cache test cleared${NC}"

# 3️⃣ Fixtures TEST uniquement (obligatoire pour tests fiables)
echo ""
echo -e "${YELLOW}3️⃣ Rechargement fixtures test...${NC}"
docker-compose exec -T backend php bin/console doctrine:database:drop --force --env=test --if-exists --quiet 2>/dev/null
docker-compose exec -T backend php bin/console doctrine:database:create --env=test --quiet
docker-compose exec -T backend php bin/console doctrine:schema:create --env=test --quiet
docker-compose exec -T backend php bin/console doctrine:fixtures:load --env=test --no-interaction --quiet
echo -e "${GREEN}✅ Fixtures test chargées${NC}"

# 4️⃣ Lancer tests avec capture de la sortie
echo ""
echo -e "${YELLOW}4️⃣ Lancement tests PHPUnit...${NC}"
echo ""

# Capturer la sortie complète
OUTPUT=$(docker-compose exec -T backend php bin/phpunit --testdox 2>&1)
TEST_RESULT=$?

# Afficher la sortie
echo "$OUTPUT"

# 5️⃣ Résultat avec statistiques détaillées
echo ""
if [ $TEST_RESULT -eq 0 ]; then
    echo "========================================="
    echo -e "${GREEN}✅ TOUS LES TESTS PASSENT${NC}"
    echo "========================================="
    
    # Extraire les statistiques
    TESTS_COUNT=$(echo "$OUTPUT" | grep -oP '\d+(?= tests)' | tail -1)
    ASSERTIONS_COUNT=$(echo "$OUTPUT" | grep -oP '\d+(?= assertions)' | tail -1)
    TIME=$(echo "$OUTPUT" | grep -oP 'Time: \K[0-9:.]+')
    MEMORY=$(echo "$OUTPUT" | grep -oP 'Memory: \K[0-9.]+ [A-Z]+')
    
    # Afficher les stats si disponibles
    if [ ! -z "$TESTS_COUNT" ]; then
        echo ""
        echo -e "${CYAN}📊 Statistiques détaillées :${NC}"
        echo -e "   ${MAGENTA}Tests exécutés :${NC} ${GREEN}$TESTS_COUNT${NC}"
        echo -e "   ${MAGENTA}Assertions :${NC} ${GREEN}$ASSERTIONS_COUNT${NC}"
        
        if [ ! -z "$TIME" ]; then
            echo -e "   ${MAGENTA}Temps d'exécution :${NC} ${BLUE}$TIME${NC}"
        fi
        
        if [ ! -z "$MEMORY" ]; then
            echo -e "   ${MAGENTA}Mémoire utilisée :${NC} ${BLUE}$MEMORY${NC}"
        fi
        
        echo ""
        echo -e "${CYAN}📁 Répartition des tests :${NC}"
        echo -e "   ${MAGENTA}Controllers :${NC} 24 tests (Project, Task, Veille)"
        echo -e "   ${MAGENTA}Security :${NC} 13 tests (Auth, Ownership, Validation)"
        echo -e "   ${MAGENTA}Entities :${NC} 14 tests (User, Project, Task, Competence)"
        echo -e "   ${MAGENTA}Documents :${NC} 8 tests (Article MongoDB)"
        echo ""
        echo "========================================="
    fi
    
    exit 0
else
    echo "========================================="
    echo -e "${RED}❌ DES TESTS ONT ÉCHOUÉ${NC}"
    echo "========================================="
    
    # Compter les échecs
    FAILURES=$(echo "$OUTPUT" | grep -oP '\d+(?= failures?)' | tail -1)
    ERRORS=$(echo "$OUTPUT" | grep -oP '\d+(?= errors?)' | tail -1)
    
    if [ ! -z "$FAILURES" ] || [ ! -z "$ERRORS" ]; then
        echo ""
        echo -e "${RED}📊 Résumé des échecs :${NC}"
        [ ! -z "$FAILURES" ] && echo -e "   ${RED}Failures :${NC} $FAILURES"
        [ ! -z "$ERRORS" ] && echo -e "   ${RED}Errors :${NC} $ERRORS"
        echo ""
    fi
    
    echo "Vérifiez les logs ci-dessus pour plus de détails."
    echo "========================================="
    exit 1
fi