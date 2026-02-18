# MY-ANKODE - Backend

Application Symfony 7.4 LTS / PHP 8.3

> Pour la documentation complète du projet, voir [/docs](../docs/) et le [README racine](../README.md).

## Lancer l'application
```bash
# Via Docker (recommandé)
docker-compose up -d
docker-compose exec backend sh
composer install
php bin/console doctrine:migrations:migrate

# Via Symfony CLI (sans Docker)
symfony serve

# Via Symfony CLI (si Docker déjà lancé sur le port 8000)
symfony serve --port=8001
```

## Lancer les tests
```bash
php bin/phpunit
# ou via script (depuis la racine du projet)
../scripts/check-tests-local.sh
```

## Variables d'environnement

Copier `.env.test.local.example` en `.env.test.local` et adapter les valeurs.

## Structure

- `src/` — Code source (Controllers, Entities, Documents, Services)
- `tests/` — Tests PHPUnit (47 tests)
- `config/` — Configuration Symfony
- `docs/` complet disponible à la racine du projet