# Workbench (DDEV) — Page Content Manager

Guide pour développer le package **en local** avec Filament, l’API et le frontend Next.js, **sans commit/push à chaque modification**.

## Deux modes de travail

| Objectif | Où travailler | Commit/push requis ? |
|----------|---------------|----------------------|
| Package PHP (blocs, Filament, API, MCP) | `xavcha_pages` + workbench | Non — lien Composer `path` |
| Rendu visuel Next.js | `xavcha-base-site/frontend` → API du workbench | Non |
| Intégration « comme en prod » dans base-site | `xavcha-base-site` + repo path (voir ci-dessous) | Non en local |
| Release / CI / autres projets | GitHub + `composer update` | Oui |

## Prérequis

- [DDEV](https://ddev.readthedocs.io/) + Docker
- Les dépôts voisins dans le monorepo local :

```
xavcha_base_site_packages/
├── xavcha_pages/          ← ce package
└── xavcha-base-site/      ← frontend Next.js (optionnel)
```

---

## 1. Démarrer le workbench (Filament + API)

Le projet DDEV s’appelle **`xavcha-pages`**. Le docroot Laravel est `workbench/public`.

```bash
cd xavcha_pages
ddev start
```

### Première installation (workbench absent ou reset)

Le dossier `workbench/` est **gitignoré**. S’il n’existe pas encore :

```bash
ddev exec composer create-project laravel/laravel workbench --prefer-dist --no-interaction

# Lier le package local
ddev composer config repositories.page-content-manager path /var/www/html --working-dir=workbench
ddev composer require filament/filament:"^4.8" laravel/mcp:"^0.5.2" xavcha/fillament-xavcha-media-library:"^1.1" xavcha/page-content-manager:@dev --working-dir=workbench

# Filament + Laravel
ddev exec cp workbench/.env.example workbench/.env
ddev exec php workbench/artisan key:generate
ddev exec php workbench/artisan filament:install --panels --no-interaction
ddev exec php workbench/artisan migrate
ddev exec php workbench/artisan storage:link
ddev exec php workbench/artisan make:filament-user
```

Enregistrer `PageResource` dans `workbench/app/Providers/Filament/AdminPanelProvider.php` (voir le workbench existant ou `docs/installation.md`).

### Installation quotidienne (workbench déjà présent)

```bash
ddev composer install -d workbench
ddev exec php workbench/artisan migrate
```

### Accès

| Service | URL |
|---------|-----|
| Filament | https://xavcha-pages.ddev.site/admin |
| API liste | https://xavcha-pages.ddev.site/api/pages |
| API page | https://xavcha-pages.ddev.site/api/pages/{slug} |
| MCP | https://xavcha-pages.ddev.site/mcp/pages |

### Commandes utiles

```bash
# Artisan (composer_root = workbench dans .ddev/config.yaml)
ddev artisan migrate
ddev artisan page-content-manager:block:list
ddev artisan page-content-manager:blocks:validate

# Tests du package
ddev exec vendor/bin/phpunit
# ou hors conteneur
composer test
```

---

## 2. Voir le frontend Next.js (sans toucher à base-site backend)

Lance **deux environnements** en parallèle :

**Terminal 1 — backend package**

```bash
cd xavcha_pages && ddev start
```

**Terminal 2 — frontend**

```bash
cd xavcha-base-site/frontend
cp .env.local.example .env.local   # si besoin
```

Dans `frontend/.env.local` :

```env
NEXT_PUBLIC_API_URL=https://xavcha-pages.ddev.site/api
NEXT_PUBLIC_FRONTEND_URL=http://localhost:3000
NEXT_PUBLIC_SITE_NAME=Workbench Pages
NEXT_PUBLIC_SITE_TAGLINE=Dev local
```

Puis :

```bash
npm install
npm run dev
```

→ http://localhost:3000

Crée/édite une page dans Filament (`xavcha-pages`) → le frontend consomme l’API du workbench. **Aucun commit, aucun `composer update`.**

### Prévisualisation brouillon (Filament → Next.js)

Dans `workbench/.env` :

```env
APP_FRONTEND_URL=http://localhost:3000
PAGE_CONTENT_MANAGER_PREVIEW_ENABLED=true
PAGE_CONTENT_MANAGER_PREVIEW_SECRET=change-me-long-random-string
```

Le bouton **Prévisualiser** dans Filament ouvrira `/preview/{slug}?preview_token=...` sur le frontend local.

---

## 3. Lier le package dans xavcha-base-site (optionnel)

Par défaut, `xavcha-base-site` installe le package **depuis GitHub** (VCS). Pour utiliser le dossier local **sans push** :

Voir **`xavcha-base-site/docs/LOCAL_PACKAGE_DEV.md`**.

Résumé : activer le montage monorepo DDEV, puis :

```bash
cd xavcha-base-site
ddev composer config repositories.page-content-manager --json '{"type":"path","url":"/var/www/xavcha_pages"}'
ddev composer require xavcha/page-content-manager:@dev
```

---

## 4. Développement du package — ce qui est instantané

Le workbench utilise un repository Composer **path** (`../` → `src/`).

| Modification | Action supplémentaire |
|--------------|----------------------|
| `src/` (blocs, Filament, services, routes) | Aucune — recharger la page |
| `config/page-content-manager.php` (package) | Republier si config copiée dans workbench : `ddev artisan vendor:publish --tag=page-content-manager-config --force` |
| `database/migrations/` (package) | `ddev artisan migrate` (migrations auto-chargées) |
| Blocs custom workbench | `workbench/app/Blocks/Custom/` |

### Vérifier le lien path

```bash
ddev composer show xavcha/page-content-manager -d workbench
```

La source doit pointer vers le répertoire parent du package.

---

## 5. Structure

```
xavcha_pages/
├── .ddev/                 # DDEV (name: xavcha-pages, composer_root: workbench)
├── docs/WORKBENCH.md      # Ce fichier
├── src/                   # Code source du package
├── config/
├── database/migrations/
├── tests/
└── workbench/             # App Laravel de test (gitignoré)
    ├── app/Providers/Filament/AdminPanelProvider.php
    └── composer.json      # repositories.path → ../
```

---

## Dépannage

### `ddev artisan` ne trouve pas artisan

Vérifier `composer_root: workbench` dans `.ddev/config.yaml`, puis `ddev restart`.

### Le frontend ne voit pas les pages

- Vérifier `NEXT_PUBLIC_API_URL=https://xavcha-pages.ddev.site/api`
- Tester : `curl -s https://xavcha-pages.ddev.site/api/pages | jq`
- Route diagnostic : http://localhost:3000/test-api (si activée dans le starter)

### Réinitialiser le workbench

```bash
ddev stop
rm -rf workbench
# Reprendre « Première installation » ci-dessus
```

### Retour au mode GitHub dans base-site

```bash
cd xavcha-base-site
ddev composer config --unset repositories.page-content-manager
ddev composer require xavcha/page-content-manager:dev-main
```
