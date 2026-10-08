# Intra-Scolaire

## Démarrage avec Docker

Prérequis : Docker Desktop démarré.

Depuis la racine du dépôt, lancez :

```powershell
docker compose up --build
```

Composer est inclus dans l’image PHP. Les dépendances sont installées
automatiquement au démarrage du conteneur `app`. L’application est ensuite
accessible à l’adresse http://localhost:8080.

Pour initialiser la base PostgreSQL après le premier démarrage :

```powershell
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

Pour arrêter les conteneurs, utilisez `Ctrl+C`, puis :

```powershell
docker compose down
```
