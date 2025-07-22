# Makefile pour simplifier les commandes du projet Symfony

# ✅ Créer la base de données
db-create:
	php bin/console doctrine:database:create --if-not-exists

# ✅ Appliquer les migrations
db-migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

# 🗑️ Supprimer la base de données (env dev)
db-drop:
	php bin/console doctrine:database:drop --force --if-exists

# 🗑️ Supprimer la base de données (env test)
db-drop-test:
	APP_ENV=test php bin/console doctrine:database:drop --force --if-exists

# 🔥 Réinitialiser la base de données (env dev)
db-reset: db-drop db-create db-migrate

# 🔥 Réinitialiser la base de données (env test)
db-reset-test:
	APP_ENV=test php bin/console doctrine:database:drop --force --if-exists
	APP_ENV=test php bin/console doctrine:database:create --if-not-exists
	APP_ENV=test php bin/console doctrine:schema:create

# 🧹 Vider le cache (env dev)
cc:
	php bin/console cache:clear

# 🧹 Vider le cache (env test)
cct:
	APP_ENV=test php bin/console cache:clear

# 🧪 Créer la base de test et le schéma
test-db:
	APP_ENV=test php bin/console doctrine:database:create --if-not-exists
	APP_ENV=test php bin/console doctrine:schema:create

# 🧪 Lancer les tests
test:
	APP_ENV=test php -d memory_limit=512M bin/phpunit

# 🕵️‍♂️ Vérifier la qualité du code avec PHPStan
stan:
	vendor/bin/phpstan analyse src tests --memory-limit=512M

# 🧽 Vérifier et corriger le code avec PHP-CS-Fixer (mode dry-run)
cs:
	vendor/bin/php-cs-fixer fix --dry-run --diff

# 🧽 Corriger automatiquement avec PHP-CS-Fixer
fix:
	vendor/bin/php-cs-fixer fix

# 🚀 Démarrer Docker
docker-up:
	docker-compose up -d

# 🛑 Arrêter Docker
docker-down:
	docker-compose down
