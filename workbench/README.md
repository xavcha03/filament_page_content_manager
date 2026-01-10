# Workbench - Environnement de test

Ce dossier contient un environnement Laravel complet pour tester le package `xavcha/page-content-manager` visuellement.

## 🚀 Installation rapide avec ddev

```bash
# 1. Installer les dépendances
ddev exec "cd workbench && composer install"

# 2. Configurer l'environnement
ddev exec "cd workbench && php artisan key:generate --force"

# 3. Exécuter les migrations
ddev exec "cd workbench && php artisan migrate --force"

# 4. Créer un utilisateur admin
ddev exec "cd workbench && php artisan tinker --execute=\"\\\$user = new App\\Models\\User(); \\\$user->name = 'Admin'; \\\$user->email = 'admin@example.com'; \\\$user->password = bcrypt('password'); \\\$user->save();\""

# 5. Publier les assets Filament
ddev exec "cd workbench && php artisan filament:assets"
```

## 📍 Accès

- **Admin Filament** : `https://xavcha-pages.ddev.site/admin`
- **API - Liste des pages** : `https://xavcha-pages.ddev.site/api/pages`
- **API - Page spécifique** : `https://xavcha-pages.ddev.site/api/pages/home`

## ✅ Fonctionnalités testables

- Ressource Pages dans le menu Filament
- Création et édition de pages
- Système de blocs de contenu
- Onglets SEO et Content
- Routes API
- Ressource de test avec page détail
- **CLI interactif pour la gestion des blocs**

## 🎨 Commandes CLI disponibles

Toutes les commandes doivent être exécutées depuis le répertoire `workbench` :

```bash
# Menu interactif principal
ddev exec "cd workbench && php artisan page-content-manager:blocks"

# Créer un nouveau bloc
ddev exec "cd workbench && php artisan page-content-manager:make-block"

# Lister les blocs
ddev exec "cd workbench && php artisan page-content-manager:block:list"

# Inspecter un bloc
ddev exec "cd workbench && php artisan page-content-manager:block:inspect hero"

# Statistiques
ddev exec "cd workbench && php artisan page-content-manager:blocks:stats"

# Valider tous les blocs
ddev exec "cd workbench && php artisan page-content-manager:blocks:validate"
```

**Note** : Pour une utilisation plus fluide, vous pouvez vous connecter au conteneur DDEV :

```bash
ddev ssh
cd workbench
php artisan page-content-manager:blocks
```

## 📝 Notes

- Le package est chargé depuis le dossier parent via le repository path dans `composer.json`
- Toutes les modifications dans le package sont immédiatement disponibles (pas besoin de `composer update`)
- La base de données SQLite est utilisée pour simplifier les tests
