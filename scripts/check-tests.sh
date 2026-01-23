#!/bin/bash
#
# Script de vérification des tests MY-ANKODE
# 
# Ce script prépare l'environnement de test et lance la suite complète :
# - Vérifie que Docker est actif
# - Nettoie les caches dev et test
# - Recharge les fixtures en environnement test (isolation des données)
# - Lance tous les tests PHPUnit avec statistiques détaillées
# - Génère le rapport de code coverage (optionnel)
#
# Usage : 
#   ./scripts/check-tests.sh           → Tests sans coverage
#   ./scripts/check-tests.sh --coverage → Tests avec coverage
#
# Prérequis : Docker Compose actif, Xdebug installé (pour coverage)
#
# Auteur : Anthony (DWWM 2026)

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
NC='\033[0m' # No Color

# Vérifier l'argument --coverage
COVERAGE_MODE=false
if [ "$1" == "--coverage" ]; then
    COVERAGE_MODE=true
    echo ""
    echo -e "${YELLOW}⚠️  Mode Coverage activé (durée : 3-4 minutes)${NC}"
fi

echo ""

# Étape 1 : Vérifier que Docker est actif
echo -e "${CYAN}1️⃣ Vérification Docker...${NC}"
if ! docker-compose ps | grep -q "Up"; then
    echo -e "${RED}❌ Erreur : Les conteneurs Docker ne sont pas actifs${NC}"
    echo "Lancez : docker-compose up -d"
    exit 1
fi
echo -e "${GREEN}✅ Conteneurs actifs${NC}"
echo ""

# Étape 2 : Nettoyage du cache
echo -e "${CYAN}2️⃣ Nettoyage cache...${NC}"
docker-compose exec backend php bin/console cache:clear --env=dev --quiet
echo -e "${GREEN}✅ Cache dev cleared${NC}"
docker-compose exec backend php bin/console cache:clear --env=test --quiet
echo -e "${GREEN}✅ Cache test cleared${NC}"
echo ""

# Étape 3 : Rechargement des fixtures test
echo -e "${CYAN}3️⃣ Rechargement fixtures test...${NC}"
docker-compose exec backend php bin/console doctrine:fixtures:load --env=test --group=test -n > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Fixtures test chargées${NC}"
else
    echo -e "${RED}❌ Erreur lors du chargement des fixtures${NC}"
    exit 1
fi
echo ""

# Étape 4 : Lancement des tests
echo -e "${CYAN}4️⃣ Lancement tests PHPUnit...${NC}"
echo ""

# Construire la commande PHPUnit
if [ "$COVERAGE_MODE" = true ]; then
    PHPUNIT_CMD="php bin/phpunit --coverage-text --coverage-html coverage"
else
    PHPUNIT_CMD="php bin/phpunit"
fi

# Exécuter les tests et capturer la sortie
OUTPUT=$(docker-compose exec backend $PHPUNIT_CMD 2>&1)
EXIT_CODE=$?

# Afficher la sortie complète
echo "$OUTPUT"
echo ""

# Analyser les résultats
if [ $EXIT_CODE -eq 0 ]; then
    echo "========================================="
    echo -e "${GREEN}✅ TOUS LES TESTS PASSENT${NC}"
    echo "========================================="
    echo ""
    
    # Extraire les statistiques
    echo -e "${BLUE}📊 Statistiques détaillées :${NC}"
    
    # Tests exécutés
    TESTS=$(echo "$OUTPUT" | grep -oP 'OK \(\K\d+(?= tests)')
    if [ ! -z "$TESTS" ]; then
        echo -e "   ${GREEN}Tests exécutés :${NC} $TESTS"
    fi
    
    # Assertions
    ASSERTIONS=$(echo "$OUTPUT" | grep -oP 'OK \(\d+ tests, \K\d+(?= assertions)')
    if [ ! -z "$ASSERTIONS" ]; then
        echo -e "   ${GREEN}Assertions :${NC} $ASSERTIONS"
    fi
    
    # Temps d'exécution
    TIME=$(echo "$OUTPUT" | grep -oP 'Time: \K[0-9:.]+')
    if [ ! -z "$TIME" ]; then
        echo -e "   ${CYAN}Temps d'exécution :${NC} $TIME"
    fi
    
    # Mémoire
    MEMORY=$(echo "$OUTPUT" | grep -oP 'Memory: \K[0-9.]+ [A-Z]+')
    if [ ! -z "$MEMORY" ]; then
        echo -e "   ${CYAN}Mémoire utilisée :${NC} $MEMORY"
    fi
    
    echo ""
    echo -e "${BLUE}📁 Répartition des tests :${NC}"
    echo -e "   ${MAGENTA}Controllers :${NC} 24 tests (Project, Task, Veille)"
    echo -e "   ${MAGENTA}Security :${NC} 13 tests (Auth, Ownership, Validation)"
    echo -e "   ${MAGENTA}Entities :${NC} 14 tests (User, Project, Task, Competence)"
    echo -e "   ${MAGENTA}Documents :${NC} 8 tests (Article MongoDB)"
    
    # Si coverage activé, extraire les stats de coverage
    if [ "$COVERAGE_MODE" = true ]; then
        echo ""
        echo -e "${BLUE}📈 Code Coverage :${NC}"
        
        # Extraire les pourcentages de coverage
        LINES_COVERAGE=$(echo "$OUTPUT" | grep -oP 'Lines:\s+\K[0-9.]+%')
        METHODS_COVERAGE=$(echo "$OUTPUT" | grep -oP 'Methods:\s+\K[0-9.]+%')
        CLASSES_COVERAGE=$(echo "$OUTPUT" | grep -oP 'Classes:\s+\K[0-9.]+%')
        
        if [ ! -z "$LINES_COVERAGE" ]; then
            echo -e "   ${GREEN}Lignes couvertes :${NC} $LINES_COVERAGE"
        fi
        
        if [ ! -z "$METHODS_COVERAGE" ]; then
            echo -e "   ${GREEN}Méthodes couvertes :${NC} $METHODS_COVERAGE"
        fi
        
        if [ ! -z "$CLASSES_COVERAGE" ]; then
            echo -e "   ${GREEN}Classes couvertes :${NC} $CLASSES_COVERAGE"
        fi
        
        echo ""
        echo -e "${YELLOW}📁 Rapport HTML disponible dans :${NC} backend/coverage/index.html"
        echo -e "${YELLOW}💡 Ouvrir avec :${NC} start backend/coverage/index.html"
    fi
    
    echo ""
    echo "========================================="
    exit 0
else
    echo "========================================="
    echo -e "${RED}❌ DES TESTS ONT ÉCHOUÉ${NC}"
    echo "========================================="
    
    # Extraire le nombre d'échecs
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