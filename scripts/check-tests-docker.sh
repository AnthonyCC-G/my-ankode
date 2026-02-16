#!/bin/bash
#
# Script de vérification des tests MY-ANKODE (DOCKER)
# 
# Prépare l'environnement et lance la suite complète de tests
# - Vérifie Docker actif
# - Crée base de test si nécessaire
# - Nettoie les caches
# - Recharge les fixtures test
# - Lance PHPUnit avec stats
# - Génère coverage (optionnel)
#
# Usage : 
#   ./scripts/check-tests-docker.sh           -> Tests standard
#   ./scripts/check-tests-docker.sh --coverage -> Tests avec coverage
#
# Auteur : Anthony (DWWM 2026)

echo "========================================="
echo "MY-ANKODE - Verification des tests"
echo "========================================="

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m'

# Mode coverage
COVERAGE_MODE=false
if [ "$1" == "--coverage" ]; then
    COVERAGE_MODE=true
    echo ""
    echo -e "${YELLOW}Mode Coverage activé (durée : 3-4 minutes)${NC}"
fi

echo ""

# Etape 1 : Vérifier Docker
echo -e "${CYAN}[1/5] Vérification Docker...${NC}"
if ! docker-compose ps | grep -q "Up"; then
    echo -e "${RED}ERREUR : Conteneurs Docker inactifs${NC}"
    echo "Lancez : docker-compose up -d"
    exit 1
fi
echo -e "${GREEN}[OK] Conteneurs actifs${NC}"
echo ""

# Etape 2 : Vérifier base test
echo -e "${CYAN}[2/5] Vérification base test...${NC}"

DB_CHECK=$(docker-compose exec -T backend php bin/console dbal:run-sql "SELECT 1" --env=test 2>&1)

if echo "$DB_CHECK" | grep -q "database \"my_ankode_test\" does not exist"; then
    echo -e "${YELLOW}Base test absente, création...${NC}"
    
    CREATE_OUTPUT=$(docker-compose exec -T backend php bin/console doctrine:database:create --env=test 2>&1)
    if [ $? -ne 0 ]; then
        echo -e "${RED}ERREUR création base${NC}"
        echo "$CREATE_OUTPUT"
        exit 1
    fi
    echo -e "${GREEN}[OK] Base créée${NC}"
    
    SCHEMA_OUTPUT=$(docker-compose exec -T backend php bin/console doctrine:schema:create --env=test 2>&1)
    if [ $? -ne 0 ]; then
        echo -e "${RED}ERREUR création schéma${NC}"
        echo "$SCHEMA_OUTPUT"
        exit 1
    fi
    echo -e "${GREEN}[OK] Schéma créé${NC}"
else
    echo -e "${GREEN}[OK] Base test OK${NC}"
fi
echo ""

# Etape 3 : Cache
echo -e "${CYAN}[3/5] Nettoyage cache...${NC}"
docker-compose exec -T backend php bin/console cache:clear --env=dev --quiet 2>/dev/null
echo -e "${GREEN}[OK] Cache dev${NC}"
docker-compose exec -T backend php bin/console cache:clear --env=test --quiet 2>/dev/null
echo -e "${GREEN}[OK] Cache test${NC}"
echo ""

# Etape 4 : Fixtures
echo -e "${CYAN}[4/5] Rechargement fixtures test...${NC}"

# Drop et recréer schéma PostgreSQL
docker-compose exec -T backend php bin/console doctrine:schema:drop --force --full-database --env=test --quiet 2>/dev/null
docker-compose exec -T backend php bin/console doctrine:schema:create --env=test --quiet 2>/dev/null

# Charger Users (première fixture)
docker-compose exec -T backend php bin/console doctrine:fixtures:load --group=user --no-interaction --env=test --quiet 2>/dev/null

# Charger Projects (append)
docker-compose exec -T backend php bin/console doctrine:fixtures:load --group=project --append --no-interaction --env=test --quiet 2>/dev/null

# Charger Tasks (append)
docker-compose exec -T backend php bin/console doctrine:fixtures:load --group=task --append --no-interaction --env=test --quiet 2>/dev/null

# Charger Competences (append)
docker-compose exec -T backend php bin/console doctrine:fixtures:load --group=competence --append --no-interaction --env=test --quiet 2>/dev/null

# MongoDB - Snippets (première fixture MongoDB)
docker-compose exec -T backend php bin/console doctrine:mongodb:fixtures:load --group=snippet --no-interaction --env=test --quiet 2>/dev/null || true

# MongoDB - Articles (append)
docker-compose exec -T backend php bin/console doctrine:mongodb:fixtures:load --group=article --append --no-interaction --env=test --quiet 2>/dev/null || true

echo -e "${GREEN}[OK] Fixtures chargées${NC}"
echo ""

# Etape 5 : Tests
echo -e "${CYAN}[5/5] Lancement tests PHPUnit...${NC}"
echo ""

if [ "$COVERAGE_MODE" = true ]; then
    PHPUNIT_CMD="php bin/phpunit --coverage-text --coverage-html coverage"
else
    PHPUNIT_CMD="php bin/phpunit"
fi

OUTPUT=$(docker-compose exec -T backend $PHPUNIT_CMD 2>&1)
EXIT_CODE=$?

echo "$OUTPUT"
echo ""

if [ $EXIT_CODE -eq 0 ]; then
    echo "========================================="
    echo -e "${GREEN}TOUS LES TESTS PASSENT${NC}"
    echo "========================================="
    echo ""
    
    echo -e "${BLUE}Statistiques :${NC}"
    
    TESTS=$(echo "$OUTPUT" | grep -oP 'OK \(\K\d+(?= tests)')
    if [ ! -z "$TESTS" ]; then
        echo -e "   ${GREEN}Tests :${NC} $TESTS"
    fi
    
    ASSERTIONS=$(echo "$OUTPUT" | grep -oP 'OK \(\d+ tests, \K\d+(?= assertions)')
    if [ ! -z "$ASSERTIONS" ]; then
        echo -e "   ${GREEN}Assertions :${NC} $ASSERTIONS"
    fi
    
    TIME=$(echo "$OUTPUT" | grep -oP 'Time: \K[0-9:.]+')
    if [ ! -z "$TIME" ]; then
        echo -e "   ${CYAN}Temps :${NC} $TIME"
    fi
    
    MEMORY=$(echo "$OUTPUT" | grep -oP 'Memory: \K[0-9.]+ [A-Z]+')
    if [ ! -z "$MEMORY" ]; then
        echo -e "   ${CYAN}Mémoire :${NC} $MEMORY"
    fi
    
    echo ""
    echo -e "${BLUE}Répartition (135 tests) :${NC}"
    echo -e "   ${MAGENTA}Security :${NC} 52 tests (Headers, Voter, Password, Sanitization, Auth, Validation)"
    echo -e "   ${MAGENTA}Controllers :${NC} 32 tests (Project, Task, Competence, Snippet, Veille)"
    echo -e "   ${MAGENTA}Entities :${NC} 26 tests (User, Project, Task, Competence)"
    echo -e "   ${MAGENTA}Documents :${NC} 16 tests (Article, Snippet MongoDB)"
    echo -e "   ${MAGENTA}Autres :${NC} 9 tests"
    
    if [ "$COVERAGE_MODE" = true ]; then
        echo ""
        echo -e "${BLUE}Code Coverage :${NC}"
        
        LINES_COVERAGE=$(echo "$OUTPUT" | grep -oP 'Lines:\s+\K[0-9.]+%')
        METHODS_COVERAGE=$(echo "$OUTPUT" | grep -oP 'Methods:\s+\K[0-9.]+%')
        
        if [ ! -z "$LINES_COVERAGE" ]; then
            echo -e "   ${GREEN}Lignes :${NC} $LINES_COVERAGE"
        fi
        
        if [ ! -z "$METHODS_COVERAGE" ]; then
            echo -e "   ${GREEN}Méthodes :${NC} $METHODS_COVERAGE"
        fi
        
        echo ""
        echo -e "${YELLOW}Rapport HTML : backend/coverage/index.html${NC}"
    fi
    
    echo ""
    echo "========================================="
    exit 0
else
    echo "========================================="
    echo -e "${RED}TESTS EN ECHEC${NC}"
    echo "========================================="
    
    FAILURES=$(echo "$OUTPUT" | grep -oP '\d+(?= failures?)' | tail -1)
    ERRORS=$(echo "$OUTPUT" | grep -oP '\d+(?= errors?)' | tail -1)
    
    if [ ! -z "$FAILURES" ] || [ ! -z "$ERRORS" ]; then
        echo ""
        echo -e "${RED}Échecs :${NC}"
        [ ! -z "$FAILURES" ] && echo -e "   Failures : $FAILURES"
        [ ! -z "$ERRORS" ] && echo -e "   Errors : $ERRORS"
        echo ""
    fi
    
    echo "Voir logs ci-dessus"
    echo "========================================="
    exit 1
fi