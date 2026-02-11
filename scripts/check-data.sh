#!/bin/bash
# ============================================
# MY-ANKODE - Vérification des données
# Check PostgreSQL + MongoDB
# ============================================

echo "======================================"
echo "🔍 MY-ANKODE - Vérification des données"
echo "======================================"
echo ""

# Se positionner dans le dossier backend
cd "$(dirname "$0")/../backend" || exit

# Couleurs pour le terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

TOTAL_ERRORS=0

echo "📊 POSTGRESQL - Base relationnelle"
echo "-----------------------------------"

# Compter les utilisateurs (SANS --quiet pour éviter le bug)
USER_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) as total FROM user_" 2>/dev/null | grep -oP '\d+' | tail -1)
if [ -z "$USER_COUNT" ]; then USER_COUNT=0; fi

if [ "$USER_COUNT" -gt 0 ] 2>/dev/null; then
    echo -e "   ${GREEN}✅${NC} Users : ${USER_COUNT} utilisateur(s)"
else
    echo -e "   ${RED}❌${NC} Users : Aucun utilisateur trouvé"
    TOTAL_ERRORS=$((TOTAL_ERRORS + 1))
fi

# Compter les projets
PROJECT_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) as total FROM project" 2>/dev/null | grep -oP '\d+' | tail -1)
if [ -z "$PROJECT_COUNT" ]; then PROJECT_COUNT=0; fi

if [ "$PROJECT_COUNT" -gt 0 ] 2>/dev/null; then
    echo -e "   ${GREEN}✅${NC} Projects : ${PROJECT_COUNT} projet(s)"
else
    echo -e "   ${RED}❌${NC} Projects : Aucun projet trouvé"
    TOTAL_ERRORS=$((TOTAL_ERRORS + 1))
fi

# Compter les tâches
TASK_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) as total FROM task" 2>/dev/null | grep -oP '\d+' | tail -1)
if [ -z "$TASK_COUNT" ]; then TASK_COUNT=0; fi

if [ "$TASK_COUNT" -gt 0 ] 2>/dev/null; then
    echo -e "   ${GREEN}✅${NC} Tasks : ${TASK_COUNT} tâche(s)"
else
    echo -e "   ${YELLOW}⚠️${NC}  Tasks : Aucune tâche trouvée"
fi

# Compter les compétences
COMPETENCE_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) as total FROM competence" 2>/dev/null | grep -oP '\d+' | tail -1)
if [ -z "$COMPETENCE_COUNT" ]; then COMPETENCE_COUNT=0; fi

if [ "$COMPETENCE_COUNT" -gt 0 ] 2>/dev/null; then
    echo -e "   ${GREEN}✅${NC} Competences : ${COMPETENCE_COUNT} compétence(s)"
else
    echo -e "   ${YELLOW}⚠️${NC}  Competences : Aucune compétence trouvée"
fi

echo ""
echo "📰 MONGODB - Base documentaire"
echo "-----------------------------------"

# Compter les articles (via mongosh local)
ARTICLE_COUNT=$(mongosh my_ankode --quiet --eval "db.articles.countDocuments()" 2>/dev/null | grep -oP '\d+' | head -1)
if [ -z "$ARTICLE_COUNT" ]; then ARTICLE_COUNT=0; fi

if [ "$ARTICLE_COUNT" -gt 0 ] 2>/dev/null; then
    echo -e "   ${GREEN}✅${NC} Articles : ${ARTICLE_COUNT} article(s)"
else
    echo -e "   ${RED}❌${NC} Articles : Aucun article trouvé"
    TOTAL_ERRORS=$((TOTAL_ERRORS + 1))
fi

# Compter les snippets
SNIPPET_COUNT=$(mongosh my_ankode --quiet --eval "db.snippets.countDocuments()" 2>/dev/null | grep -oP '\d+' | head -1)
if [ -z "$SNIPPET_COUNT" ]; then SNIPPET_COUNT=0; fi

if [ "$SNIPPET_COUNT" -gt 0 ] 2>/dev/null; then
    echo -e "   ${GREEN}✅${NC} Snippets : ${SNIPPET_COUNT} snippet(s)"
else
    echo -e "   ${YELLOW}⚠️${NC}  Snippets : Aucun snippet trouvé"
fi

echo ""
echo "======================================"

# Verdict final
if [ $TOTAL_ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ TOUT EST OK !${NC}"
    echo "   Vous pouvez lancer votre présentation."
else
    echo -e "${RED}❌ ERREURS DÉTECTÉES : ${TOTAL_ERRORS}${NC}"
    echo -e "   ${YELLOW}⚠️  Lancez le script de reset :${NC}"
    echo "      bash scripts/reset-all-fixtures.sh"
fi

echo "======================================"
echo ""