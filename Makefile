# Makefile pour simplifier les commandes du projet Symfony

.PHONY: help

# 📋 Aide par défaut
help:
	@echo "=================== COMMANDES DISPONIBLES ==================="
	@echo ""
	@echo "🗄️  BASE DE DONNÉES:"
	@echo "  make db-create      - Créer la base de données"
	@echo "  make db-migrate     - Appliquer les migrations"
	@echo "  make db-drop        - Supprimer la base de données (dev)"
	@echo "  make db-reset       - Réinitialiser la base de données (dev)"
	@echo "  make db-drop-test   - Supprimer la base de données (test)"
	@echo "  make db-reset-test  - Réinitialiser la base de données (test)"
	@echo ""
	@echo "🐰 RABBITMQ MESSENGER:"
	@echo "  make msg-consume    - Consommer les messages"
	@echo "  make msg-stats      - Statistiques des messages"
	@echo "  make msg-stop       - Arrêter les workers"
	@echo "  make msg-debug      - Debug des transports"
	@echo "  make msg-failed     - Voir les messages échoués"
	@echo "  make msg-retry      - Relancer les messages échoués"
	@echo ""
	@echo "🧪 TESTS & QUALITÉ:"
	@echo "  make test-db        - Créer la base de test"
	@echo "  make test           - Lancer les tests"
	@echo "  make stan           - Analyser avec PHPStan"
	@echo "  make cs             - Vérifier le code (dry-run)"
	@echo "  make fix            - Corriger le code automatiquement"
	@echo ""
	@echo "🛠️  OUTILS:"
	@echo "  make cc             - Vider le cache (dev)"
	@echo "  make cct            - Vider le cache (test)"
	@echo "  make exec-command   - Lancer import CSV"
	@echo "  make docker-up      - Démarrer Docker"
	@echo "  make docker-down    - Arrêter Docker"
	@echo ""

# =============================================================================
# 🗄️ GESTION BASE DE DONNÉES
# =============================================================================

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

# =============================================================================
# 🐰 RABBITMQ MESSENGER
# =============================================================================

# ✅ Consommer les messages
msg-consume:
	php bin/console messenger:consume async -vv

# 📊 Statistiques des messages
msg-stats:
	php bin/console messenger:stats

# 🛑 Arrêter les workers
msg-stop:
	php bin/console messenger:stop-workers

# 🔍 Debug des transports
msg-debug:
	php bin/console debug:messenger

# ❌ Voir les messages échoués
msg-failed:
	php bin/console messenger:failed:show

# 🔄 Relancer les messages échoués
msg-retry:
	php bin/console messenger:failed:retry

# 🗑️ Supprimer les messages échoués
msg-remove:
	php bin/console messenger:failed:remove

# 🚀 Worker production avec limites
msg-prod:
	php bin/console messenger:consume async --memory-limit=128M --time-limit=3600

# =============================================================================
# 🧪 TESTS & QUALITÉ DE CODE
# =============================================================================

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

# 🧽 Vérifier le code avec PHP-CS-Fixer (mode dry-run)
cs:
	vendor/bin/php-cs-fixer fix --dry-run --diff

# 🧽 Corriger automatiquement avec PHP-CS-Fixer
fix:
	vendor/bin/php-cs-fixer fix

# =============================================================================
# 🛠️ OUTILS DIVERS
# =============================================================================

# 🧹 Vider le cache (env dev)
cc:
	php bin/console cache:clear

# 🧹 Vider le cache (env test)
cct:
	APP_ENV=test php bin/console cache:clear

# ✅ Lancer commande import CSV
exec-command:
	php bin/console app:update-contact

# 🚀 Démarrer Docker
docker-up:
	docker-compose up -d

# 🛑 Arrêter Docker
docker-down:
	docker-compose down

# =============================================================================
# 🔗 RACCOURCIS COMBINÉS
# =============================================================================

# 🏁 Setup complet du projet
setup: docker-up db-create db-migrate cc
	@echo "✅ Projet configuré avec succès !"

# 🧹 Nettoyage complet
clean: cc cct docker-down
	@echo "✅ Nettoyage terminé !"

# 🧪 Pipeline de tests complet
ci: cs stan test
	@echo "✅ Pipeline CI terminé !"
