#!/bin/bash
echo "========================================="
echo "🧪 MY-ANKODE - Vérification des tests"
echo "========================================="

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

# 4️⃣ Lancer tests
echo ""
echo -e "${YELLOW}4️⃣ Lancement tests PHPUnit...${NC}"
echo ""
docker-compose exec -T backend php bin/phpunit --testdox

# 5️⃣ Résultat
TEST_RESULT=$?
echo ""
if [ $TEST_RESULT -eq 0 ]; then
    echo "========================================="
    echo -e "${GREEN}✅ TOUS LES TESTS PASSENT${NC}"
    echo "========================================="
    exit 0
else
    echo "========================================="
    echo -e "${RED}❌ DES TESTS ONT ÉCHOUÉ${NC}"
    echo "========================================="
    exit 1
fi