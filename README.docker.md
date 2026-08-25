# Docker - GestLibr

Cette configuration met en place 3 conteneurs:
- `web`: Apache + PHP (application mappée sur `./web` en dev)
- `mysql`: serveur MySQL
- `phpmyadmin`: interface phpMyAdmin préconfigurée sur `mysql`

## Fichiers

- `compose.yaml`: configuration commune
- `compose.dev.yaml`: overrides développement local
- `compose.prod.yaml`: overrides production
- `.env.dev`: variables d'environnement développement
- `.env.prod`: variables d'environnement production
- `docker/php/Dockerfile`: image PHP 8.2 / Apache
- `docker/php/conf.d/legacy.ini`: options PHP (dont `short_open_tag=On`)
- `docker/php/conf.d/dev.ini`: options PHP de debug (dev uniquement)
- `docker/mysql/data/`: données MySQL persistées sur le projet (bind mount)

## Démarrage en développement

```bash
docker compose --env-file .env.dev -f compose.yaml -f compose.dev.yaml up -d --build
```

Accès:
- Site: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`
- MySQL: `localhost:3306`

En mode dev, les erreurs PHP sont affichées directement sur les pages (`display_errors=On`) avec un niveau adapté au debug sans bruit de notices (`E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED`).

## Démarrage en production

```bash
docker compose --env-file .env.prod -f compose.yaml -f compose.prod.yaml up -d --build
```

En production, la stack cible est limitée à `web` + `mysql` (pas de phpMyAdmin).
Le conteneur `web` est prévu pour être exposé via un reverse proxy (Caddy) sur HTTPS.

## Arrêt

```bash
docker compose --env-file .env.dev -f compose.yaml -f compose.dev.yaml down
```

## Réinitialiser complètement la base (attention: suppression des données)

```bash
docker compose --env-file .env.dev -f compose.yaml -f compose.dev.yaml down -v
```

Puis supprimer le contenu de `./docker/mysql/data` si nécessaire.

## Initialisation de la base

Le fichier SQL référencé par `MYSQL_INIT_FILE` (par défaut `./docker/mysql/init/01-gestlibr.sql`) est exécuté automatiquement **au premier démarrage** lorsque le volume MySQL est vide.
Les fichiers de données MySQL sont stockés dans `MYSQL_DATA_DIR` (par défaut `./docker/mysql/data`).
Un second script `./docker/mysql/init/02-ebay-listing-state.sql` est chargé automatiquement pour la table eBay REST (`ebay_listing_state`).
Un troisième script `./docker/mysql/init/03-bookphoto-sort-order.sql` est chargé automatiquement (premier init uniquement) pour ajouter `bookphoto.sortOrder` et backfiller l'ordre des photos.

Pour une base de prod existante, exécuter manuellement le script:

```bash
mysql -h <host> -u <user> -p <database> < docs/sql/03-bookphoto-sort-order.sql
```

Le dump fourni par l'application (`web/gestlibr-struct.sql`) utilise l'ancienne syntaxe `TYPE=MyISAM`.  
Le fichier `docker/mysql/init/01-gestlibr.sql` est une version compatible MySQL 5.7 (`ENGINE=MyISAM`).

## Compatibilité des architectures

Le développement conserve MySQL 5.7 pour compatibilité historique. La production utilise explicitement MySQL 8.0 en `linux/arm64/v8` sur le serveur ARM64. La migration entre les deux architectures doit toujours passer par un dump SQL logique; le répertoire physique `/var/lib/mysql` ne doit jamais être copié entre les serveurs.

## Variables eBay

Les fichiers `.env.dev` / `.env.prod` contiennent les variables eBay:
- `EBAY_PROVIDER` (`legacy` ou `rest`)
- `EBAY_ENV` (`sandbox` ou `production`)
- `EBAY_CLIENT_ID`, `EBAY_CLIENT_SECRET`, `EBAY_REDIRECT_URI`
- `EBAY_MARKETPLACE_ID`
- `EBAY_MERCHANT_LOCATION_KEY`
- `EBAY_PAYMENT_POLICY_ID`, `EBAY_FULFILLMENT_POLICY_ID`, `EBAY_RETURN_POLICY_ID`
- `EBAY_REFRESH_TOKEN` (prioritaire sur le token stocké en base)

## Déploiement vers le serveur de production

Le script `scripts/deploy-prod.sh` déploie le projet depuis la machine de dev vers un serveur distant SSH.

Exemple (déploiement standard):

```bash
scripts/deploy-prod.sh \
  --host manager.wiroo.com \
  --user ubuntu \
  --target-dir /srv/gestlibr
```

Le script:
- synchronise les assets de déploiement dans `/srv/gestlibr/deploy` (compose, docker, scripts),
- synchronise les fichiers applicatifs servis dans `/srv/gestlibr/web` (avec `--delete`),
- **préserve strictement** `/srv/gestlibr/web/pictures`,
- stocke MySQL dans `/srv/gestlibr/mysql`,
- crée un backup SQL gzip avant upgrade dans `/srv/gestlibr/backups/mysql`,
- applique les migrations SQL incrémentales (`docker/mysql/migrations`) via `schema_migrations`,
- connecte le service web au réseau Docker externe `proxy` avec l'alias `gestlibr-web`.

Le script ne lit, ne modifie et ne recharge jamais le Caddyfile. La configuration Caddy est gérée séparément par l'administrateur du serveur.

### Paramètres du script `deploy-prod.sh`

```bash
scripts/deploy-prod.sh [options]

Options:
  --host <host>                  SSH host (défaut: manager.wiroo.com)
  --user <user>                  SSH user (défaut: ubuntu)
  --target-dir <path>            Répertoire de base distant (défaut: /srv/gestlibr)
  --project-name <name>          Nom de projet compose (défaut: gestlibr-prod)
  --proxy-network <name>         Réseau Docker partagé avec Caddy (défaut: proxy)
  --env-file-local <path>        Fichier env local à pousser en prod
  --sync-mysql-secrets           Pousse aussi les secrets MySQL locaux
  --dry-run                      Simule sans appliquer le déploiement distant
```

### Gestion du fichier `.env.prod`

- Sans option `--env-file-local`, le script utilise automatiquement `./.env.prod` local s'il existe.
- Le fichier est déployé sur le serveur dans `/srv/gestlibr/env/.env.prod` avec les droits `0600`.
- Par défaut, les variables MySQL suivantes sont **préservées depuis la prod** (pour éviter une casse DB accidentelle):
  - `MYSQL_DATABASE`
  - `MYSQL_USER`
  - `MYSQL_PASSWORD`
  - `MYSQL_ROOT_PASSWORD`
- Pour forcer la rotation des secrets MySQL avec les valeurs locales, utiliser explicitement `--sync-mysql-secrets`.

### Commandes recommandées

Déploiement applicatif normal (code + variables applicatives type eBay, sans rotation MySQL):

```bash
scripts/deploy-prod.sh \
  --host manager.wiroo.com \
  --user ubuntu \
  --target-dir /srv/gestlibr
```

Déploiement avec rotation volontaire des secrets MySQL:

```bash
scripts/deploy-prod.sh \
  --host manager.wiroo.com \
  --user ubuntu \
  --target-dir /srv/gestlibr \
  --sync-mysql-secrets
```

Simulation (sans exécution distante):

```bash
scripts/deploy-prod.sh --dry-run
```

### Notes sécurité / exploitation

- Le script refuse une cible de production différente de `manager.wiroo.com`.
- Le script de déploiement crée un backup SQL gzip dans `/srv/gestlibr/backups/mysql` avant migration.
- Les migrations SQL sont idempotentes et pilotées par la table `schema_migrations`.
- En cas d'indisponibilité du compte root MySQL, les scripts utilisent un fallback sur le compte applicatif pour backup/migrations (si privilèges suffisants).
- Le reverse proxy Caddy doit utiliser `reverse_proxy gestlibr-web:80` et être lui-même connecté au réseau Docker `proxy`.
