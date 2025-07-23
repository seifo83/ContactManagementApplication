# 📇 Gestion de l’import CSV des Contacts

Un système performant de gestion de **contacts** et d'**organisations**, conçu pour traiter de **gros volumes de données** avec une architecture moderne basée sur **Symfony 7.3** et **PHP 8.4**.

---

## 🚀 Fonctionnalités principales

- 📝 **Import massif** de contacts et organisations depuis fichiers CSV
- 🔄 **Traitement par chunks** pour optimiser la mémoire
- 🧵 **Gestion asynchrone** avec Symfony Messenger et RabbitMQ
- 🗂 **Mise à jour** et suppression logique des données obsolètes
- 🧠 **Architecture DDD + CQRS** pour une meilleure maintenabilité
- ✅ **Tests robustes** unitaires et fonctionnels

---

## 🧰 Stack technique

| Composant       | Technologie         | Version      |
| --------------- | ------------------- |--------------|
| Framework       | Symfony             | 7.3          |
| Langage         | PHP                 | 8.4          |
| Queue messages  | Messenger + RabbitMQ | 3-management |
| Base de données | PostgreSQL          | 16-alpine    |
| Cache           | Redis               | alpine       |
| Admin DB        | pgAdmin             | 4            |
| Environnement   | Docker Compose      | -            |
| Tests           | PHPUnit             | -            |
| Qualité code    | PHPStan             | Niveau 6     |
| Formatage code  | PHP-CS-Fixer        |              |

---

## 📦 Installation

### 1. Cloner le projet
```bash
git clone https://github.com/seifo83/ContactManagementApplication.git
cd ContactManagementApplication
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Configurer l'environnement
```bash
cp .env.local.dist .env.local
# Modifiez les variables (base de données, RabbitMQ, etc.) si nécessaire
```

### 4. Démarrer les services (optionnel)
```bash
make docker-up
```

### 5. Configurer la base de données
```bash
make setup  # Configure tout automatiquement
```

**Ou manuellement :**
```bash
make db-create
make db-migrate
```

---

## 🏃‍♂️ Utilisation

### Import de données CSV
```bash
make exec-command
# ou directement :
php bin/console app:update-contact chemin/vers/votre/fichier.csv
```

### Traitement des messages asynchrones
```bash
make msg-consume
# ou directement :
php bin/console messenger:consume async -vv
```

### Surveiller les messages
```bash
make msg-stats
```

---

## 🛠️ Commandes disponibles

### Base de données
```bash
make db-create      # Créer la base de données
make db-migrate     # Appliquer les migrations
make db-reset       # Réinitialiser (dev)
make db-reset-test  # Réinitialiser (test)
```

### RabbitMQ Messenger
```bash
make msg-consume    # Consommer les messages
make msg-stats      # Statistiques des messages
make msg-stop       # Arrêter les workers
make msg-debug      # Debug des transports
make msg-failed     # Voir les messages échoués
make msg-retry      # Relancer les messages échoués
```

### Tests et qualité
```bash
make test           # Lancer les tests
make stan           # Analyser avec PHPStan
make cs             # Vérifier le code (dry-run)
make fix            # Corriger le code automatiquement
```

### Outils
```bash
make cc             # Vider le cache (dev)
make docker-up      # Démarrer Docker
make docker-down    # Arrêter Docker
make help           # Voir toutes les commandes
```

### Raccourcis combinés
```bash
make setup          # Configuration complète du projet
make clean          # Nettoyage complet
make ci             # Pipeline de tests complet
```

---

## 🧪 Tests

### Lancer tous les tests
```bash
make test
```

### Analyse statique
```bash
make stan
```

### Pipeline complet
```bash
make ci  # Équivalent à : cs + stan + test
```

---

## 🔥 Optimisations mémoire

Le projet utilise plusieurs techniques d'optimisation :

- **Traitement par lots (chunks)** pour les gros fichiers
- `EntityManager->clear()` et `gc_collect_cycles()` après chaque batch
- **Handler unifié** pour simplifier le code
- **Gestion asynchrone** pour éviter les timeouts

---

## 🗂️ Structure du projet

```
src/
├── Application/        # Cas d'usage (messages, handlers, services)
├── Entity/             # Entités Doctrine
├── Repository/         # Accès aux données
├── Command/            # Commandes Symfony CLI
tests/                  # Tests unitaires et fonctionnels
```

---

## 🔍 Architecture

Le projet suit une architecture orientée DDD et CQRS avec séparation claire :

> 📘 Pour les détails complets de l’architecture :  
👉 [Voir ARCHITECTURE.md](./ARCHITECTURE.md)

---

## 🚦 Démarrage rapide

```bash
# 1. Cloner et installer
git clone https://github.com/seifo83/ContactManagementApplication.git
cd ContactManagementApplication
composer install

# 2. Configuration complète
make setup

# 3. Lancer un import
make exec-command

# 4. Consommer les messages
make msg-consume
```

---

Happy coding!
