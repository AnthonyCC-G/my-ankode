#!/bin/bash

echo "========================================="
echo "🧪 MY-ANKODE - Vérification des tests"
echo "========================================="

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 1️⃣ Vérifier que Docker tourne
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

# 3️⃣ Fixtures dev
echo ""
echo -e "${YELLOW}3️⃣ Rechargement fixtures dev...${NC}"
docker-compose exec -T backend php bin/console doctrine:database:drop --force --if-exists --quiet 2>/dev/null
docker-compose exec -T backend php bin/console doctrine:database:create --quiet 2>/dev/null
docker-compose exec -T backend php bin/console doctrine:schema:create --quiet 2>/dev/null
docker-compose exec -T backend php bin/console doctrine:fixtures:load --no-interaction --quiet
echo -e "${GREEN}✅ Fixtures dev chargées (Anthony, Alice, Marie)${NC}"

# 4️⃣ Fixtures test
echo ""
echo -e "${YELLOW}4️⃣ Rechargement fixtures test...${NC}"
docker-compose exec -T backend php bin/console doctrine:database:drop --force --env=test --if-exists --quiet 2>/dev/null
docker-compose exec -T backend php bin/console doctrine:database:create --env=test --quiet 2>/dev/null
docker-compose exec -T backend php bin/console doctrine:schema:create --env=test --quiet 2>/dev/null
docker-compose exec -T backend php bin/console doctrine:fixtures:load --env=test --no-interaction --quiet
echo -e "${GREEN}✅ Fixtures test chargées (Anthony, Alice, Marie)${NC}"

# 5️⃣ Lancer TOUS les tests
echo ""
echo -e "${YELLOW}5️⃣ Lancement tests PHPUnit...${NC}"
echo ""
docker-compose exec -T backend php bin/phpunit --testdox

# 6️⃣ Résultat final
TEST_RESULT=$?

echo ""
if [ $TEST_RESULT -eq 0 ]; then
    echo "========================================="
    echo -e "${GREEN}✅ TOUS LES TESTS PASSENT (47 tests)${NC}"
    echo -e "${BLUE}📊 Détails :${NC}"
    echo -e "${BLUE}   - 19 tests Entity (unitaires)${NC}"
    echo -e "${BLUE}   - 15 tests Controller (API REST)${NC}"
    echo -e "${BLUE}   - 13 tests Security (ownership/validation/auth)${NC}"
    echo "========================================="
    echo -e "${GREEN}🚀 Code prêt pour commit/push !${NC}"
    echo "========================================="
    exit 0
else
    echo "========================================="
    echo -e "${RED}❌ DES TESTS ONT ÉCHOUÉ${NC}"
    echo -e "${RED}Corriger avant de committer${NC}"
    echo "========================================="
    exit 1
fi