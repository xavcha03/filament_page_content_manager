# Guide d'installation

## Prérequis

- PHP >= 8.2
- Laravel >= 12.0
- Filament >= 4.0

## Installation

### 0. Dépendance requise : Media Library

Ce package nécessite `xavcha/fillament-xavcha-media-library` qui est disponible sur GitHub.

#### Si la media library n'est PAS encore installée

**Ajoutez le repository dans votre `composer.json`** :

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

Puis installez normalement :
```bash
composer require xavcha/page-content-manager
```

#### Si la media library est DÉJÀ installée

**Option A** : Si le repository est déjà dans votre `composer.json`, installez simplement :
```bash
composer require xavcha/page-content-manager
```

**Option B** : Si vous avez des problèmes de résolution de dépendances :
```bash
# Installer sans mettre à jour les dépendances existantes
composer require xavcha/page-content-manager --no-update
composer update xavcha/page-content-manager --with-dependencies
```

Voir [Gestion des Dépendances](dependencies.md) pour plus de détails et le dépannage.

### 1. Installation via Composer

```bash
composer require xavcha/page-content-manager
```

### 2. Publier la configuration

```bash
php artisan vendor:publish --tag=page-content-manager-config
```

Cela créera le fichier `config/page-content-manager.php` dans votre projet.

### 3. Exécuter les migrations

```bash
php artisan migrate
```

Cela créera la table `pages` avec une page Home par défaut.

### 4. Enregistrer la ressource Filament

**IMPORTANT** : Vous devez enregistrer manuellement la ressource Filament dans votre `PanelProvider`.

Ouvrez votre fichier `app/Providers/Filament/AdminPanelProvider.php` (ou le PanelProvider de votre panel) et ajoutez :

```php
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        // ... autres configurations ...
        ->resources([
            PageResource::class,
            // ... autres ressources
        ]);
}
```

### 5. Vérifier l'installation

Une fois la ressource enregistrée, vous devriez voir la ressource **Pages** dans votre panel Filament.

## Configuration

La configuration se trouve dans `config/page-content-manager.php`. Les principales options sont :

- `routes` : Active/désactive les routes API (défaut: `true`)
- `route_prefix` : Préfixe des routes API (défaut: `api`)
- `register_filament_resource` : Tente d'enregistrer automatiquement la ressource Filament (défaut: `false`, **non recommandé**)
- `blocks` : Configuration des blocs disponibles

## Note sur l'enregistrement automatique

L'enregistrement automatique via `register_filament_resource` peut ne pas fonctionner correctement car les routes ne sont pas créées à temps. Il est **fortement recommandé** d'enregistrer manuellement la ressource dans votre `PanelProvider` comme indiqué ci-dessus.

