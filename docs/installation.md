# Guide d'installation

## Prérequis

- PHP >= 8.2
- Laravel >= 12.0
- Filament >= 4.0

## Installation

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

### 4. Vérifier l'installation

Une fois installé, vous devriez voir la ressource **Pages** dans votre panel Filament.

## Configuration

La configuration se trouve dans `config/page-content-manager.php`. Les principales options sont :

- `routes` : Active/désactive les routes API (défaut: `true`)
- `route_prefix` : Préfixe des routes API (défaut: `api`)
- `register_filament_resource` : Enregistre automatiquement la ressource Filament (défaut: `true`)
- `blocks` : Configuration des blocs disponibles

## Désactiver l'enregistrement automatique

Si vous préférez enregistrer manuellement la ressource Filament, dans `config/page-content-manager.php` :

```php
'register_filament_resource' => false,
```

Puis dans votre `PanelProvider` :

```php
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;

public function panel(Panel $panel): Panel
{
    return $panel
        ->resources([
            PageResource::class,
            // ... autres ressources
        ]);
}
```

