# Gestion des Dépendances

## Dépendance requise : Media Library

Le package `xavcha/page-content-manager` nécessite le package `xavcha/fillament-xavcha-media-library` qui est disponible publiquement sur GitHub.

## Installation

### Option 1 : Ajouter le repository dans votre projet (Recommandé)

Ajoutez le repository VCS dans le `composer.json` de votre projet Laravel :

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/xavcha03/fillament_xavcha_media_library"
    }
  ],
  "require": {
    "xavcha/page-content-manager": "^2.0"
  }
}
```

Puis installez :

```bash
composer require xavcha/page-content-manager
```

Composer installera automatiquement `xavcha/fillament-xavcha-media-library` depuis GitHub.

### Option 2 : Installer manuellement la media library d'abord

Si vous préférez installer la media library séparément :

```bash
# 1. Ajouter le repository
composer config repositories.fillament-xavcha-media-library vcs https://github.com/xavcha03/fillament_xavcha_media_library

# 2. Installer la media library
composer require xavcha/fillament-xavcha-media-library:dev-main

# 3. Installer le page-content-manager
composer require xavcha/page-content-manager
```

### Option 3 : Si la media library est déjà installée

Si vous avez déjà `xavcha/fillament-xavcha-media-library` installé dans votre projet, vous avez deux options :

#### Option 3a : Installation normale (si le repository est déjà configuré)

Si le repository VCS est déjà dans votre `composer.json`, installez simplement :

```bash
composer require xavcha/page-content-manager
```

Composer utilisera automatiquement la version existante de la media library.

#### Option 3b : Installation sans mettre à jour les dépendances (si problème de résolution)

Si Composer a des problèmes à résoudre la dépendance, installez sans mettre à jour :

```bash
# Installer sans mettre à jour les dépendances existantes
composer require xavcha/page-content-manager --no-update

# Puis mettre à jour uniquement ce package
composer update xavcha/page-content-manager --with-dependencies
```

**Ou** si vous voulez forcer l'utilisation de la version déjà installée :

```bash
# Ajouter le repository si pas déjà présent
composer config repositories.fillament-xavcha-media-library vcs https://github.com/xavcha03/fillament_xavcha_media_library

# Installer le package
composer require xavcha/page-content-manager --ignore-platform-reqs
```

**Note** : L'option `--ignore-platform-reqs` ignore les vérifications de plateforme mais ne résout pas le problème de dépendance. Utilisez-la seulement si vous êtes sûr que la media library est compatible.

## Vérification

Après l'installation, vérifiez que les deux packages sont bien installés :

```bash
composer show xavcha/fillament-xavcha-media-library
composer show xavcha/page-content-manager
```

## Configuration de la Media Library

Une fois installée, vous devez :

1. **Publier les migrations** :
```bash
php artisan vendor:publish --tag=media-library-pro-migrations
php artisan migrate
```

2. **Publier les assets Filament** :
```bash
php artisan filament:assets
```

3. **Ajouter la page MediaLibrary dans votre PanelProvider** (optionnel mais recommandé) :

```php
use Xavier\MediaLibraryPro\Pages\MediaLibraryPage;

public function panel(Panel $panel): Panel
{
    return $panel
        ->pages([
            Pages\Dashboard::class,
            MediaLibraryPage::class, // Ajoutez cette ligne
        ]);
}
```

## Dépannage

### Erreur : "Could not find package xavcha/fillament-xavcha-media-library"

**Solution 1** : Ajoutez le repository VCS dans votre `composer.json` :

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/xavcha03/fillament_xavcha_media_library"
    }
  ]
}
```

Puis réessayez :
```bash
composer require xavcha/page-content-manager
```

**Solution 2** : Si la media library est déjà installée, installez sans mettre à jour :

```bash
composer require xavcha/page-content-manager --no-update
composer update xavcha/page-content-manager --with-dependencies
```

**Solution 3** : Vérifiez que la media library est bien installée :

```bash
composer show xavcha/fillament-xavcha-media-library
```

Si elle n'est pas installée, installez-la d'abord :

```bash
composer config repositories.fillament-xavcha-media-library vcs https://github.com/xavcha03/fillament_xavcha_media_library
composer require xavcha/fillament-xavcha-media-library:dev-main
composer require xavcha/page-content-manager
```

### Erreur : "no such table: media_files"

**Solution** : Publiez et exécutez les migrations de la media library :

```bash
php artisan vendor:publish --tag=media-library-pro-migrations
php artisan migrate
```

### Le menu "Médias" n'apparaît pas dans Filament

**Solution** : Ajoutez `MediaLibraryPage::class` dans votre `PanelProvider` (voir section Configuration ci-dessus).

