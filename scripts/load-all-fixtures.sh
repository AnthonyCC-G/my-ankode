#!/bin/bash

echo " Chargement complet des fixtures MY-ANKODE"

# 1. PostgreSQL base
echo " PostgreSQL : Users, Projects, Tasks..."
php bin/console doctrine:fixtures:load --no-interaction

# 2. MongoDB
echo " MongoDB : Articles, Snippets..."
php bin/console doctrine:mongodb:fixtures:load --no-interaction

# 3. Competences (dépend de tout)
echo " PostgreSQL : Competences..."
mkdir -p fixtures_backup
mv src/DataFixtures/AppFixtures.php fixtures_backup/
mv src/DataFixtures/UserFixtures.php fixtures_backup/
mv src/DataFixtures/ProjectFixtures.php fixtures_backup/
mv src/DataFixtures/TaskFixtures.php fixtures_backup/
mv src/DataFixtures/ArticleFixtures.php fixtures_backup/
mv src/DataFixtures/SnippetFixtures.php fixtures_backup/

php bin/console doctrine:fixtures:load --append --no-interaction

mv fixtures_backup/* src/DataFixtures/
rmdir fixtures_backup

echo " Toutes les fixtures chargées !"