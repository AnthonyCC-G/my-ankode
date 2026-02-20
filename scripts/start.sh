#!/bin/bash
# ============================================
# Script de démarrage - MY-ANKODE
# 
# Usage: ./scripts/start.sh
# Objectif: Démarrer l'environnement de développement complet
# Prérequis: Docker Desktop installé et lancé
# ============================================

set -e  # Arrête le script si une commande échoue

cd "$(dirname "$0")/.." || exit 1

echo ""
echo "MY-ANKODE - Demarrage environnement Docker"
echo "==========================================="
echo ""

# ------------------------------------------
# ETAPE 1 : Verification des prerequis
# ------------------------------------------
echo "[1/5] Verification des prerequis..."

# Docker installé ?
if ! command -v docker &> /dev/null; then
    echo "ERREUR : Docker n'est pas installe ou pas dans le PATH"
    echo "  Telechargez Docker Desktop : https://www.docker.com/products/docker-desktop"
    exit 1
fi

# Docker daemon lancé ?
if ! docker info &> /dev/null; then
    echo "ERREUR : Docker Desktop n'est pas lance"
    echo "  Demarrez Docker Desktop puis relancez ce script"
    exit 1
fi

# Fichier .env present a la racine ?
if [ ! -f ".env" ]; then
    echo "ERREUR : Fichier .env manquant a la racine du projet"
    echo "  Creez-le depuis le template : cp .env.example .env"
    echo "  Puis renseignez vos credentials dans .env"
    exit 1
fi

echo "  OK - Prerequis valides"

# ------------------------------------------
# ETAPE 2 : Demarrage des conteneurs
# ------------------------------------------
echo ""
echo "[2/5] Demarrage des conteneurs Docker..."
docker compose up -d --build

echo "  OK - Conteneurs lances"

# ------------------------------------------
# ETAPE 3 : Attente que les services soient prets
# ------------------------------------------
echo ""
echo "[3/5] Attente que PostgreSQL et MongoDB soient prets..."

MAX_WAIT=60
WAIT=0

until docker compose exec -T postgres pg_isready -U "${POSTGRES_USER:-ankode_user}" &> /dev/null; do
    if [ $WAIT -ge $MAX_WAIT ]; then
        echo "ERREUR : PostgreSQL n'a pas demarre apres ${MAX_WAIT}s"
        echo "  Consultez les logs : docker compose logs postgres"
        exit 1
    fi
    printf "."
    sleep 2
    WAIT=$((WAIT + 2))
done

echo ""
echo "  OK - Bases de donnees pretes"

# ------------------------------------------
# ETAPE 4 : Migrations et schema
# ------------------------------------------
echo ""
echo "[4/5] Application des migrations..."

docker compose exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T backend php bin/console doctrine:mongodb:schema:create --no-interaction 2>/dev/null || true

echo "  OK - Schema a jour"

# Prechauffage du cache Symfony
echo ""
echo "  Prechauffage du cache Symfony..."
docker compose exec -T backend php bin/console cache:warmup
echo "  OK - Cache prechauffé"

# ------------------------------------------
# ETAPE 5 : Chargement des fixtures (optionnel)
# ------------------------------------------
echo ""
echo "[5/5] Chargement des donnees de demonstration ?"
echo "  Attention : cette etape ecrase toutes les donnees existantes"
printf "  Charger les fixtures ? [o/N] : "
read -r LOAD_FIXTURES

if [[ "$LOAD_FIXTURES" =~ ^[oO]$ ]]; then
    echo "  Chargement en cours..."
    bash scripts/reset-all-fixtures-docker.sh
    echo "  OK - Fixtures chargees"
else
    echo "  Fixtures ignorees - donnees existantes conservees"
fi

# ------------------------------------------
# RECAPITULATIF
# ------------------------------------------
echo ""
echo "==========================================="
echo "Environnement pret !"
echo ""
echo "Acces :"
echo "  Application   : http://localhost:8000"
echo "  pgAdmin       : http://localhost:5050"
echo "  Mongo Express : http://localhost:8081"
echo ""
echo "Compte de test :"
echo "  Email         : anthony@myankode.com"
echo "  Mot de passe  : (voir fixtures UserFixtures.php)"
echo ""
echo "Commandes utiles :"
echo "  Logs          : docker compose logs -f backend"
echo "  Shell         : docker compose exec backend bash"
echo "  Tests         : docker compose exec backend php bin/phpunit"
echo "  Arreter       : docker compose down"
echo "==========================================="
echo ""