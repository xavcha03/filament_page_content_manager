# Architecture des blocs

## Structure

```
src/Blocks/
├── Contracts/BlockInterface.php
├── Concerns/HasMediaTransformation.php
├── Concerns/HasMcpMetadata.php
├── Core/              # Blocs core du package
├── BlockRegistry.php  # Auto-decouverte + cache
└── SectionTransformer.php
```

## Regles

- Un bloc = 1 fichier (formulaire + transform)
- Auto-decouverte :
  - Core : `src/Blocks/Core/`
  - Custom : `app/Blocks/Custom/`
- Source of truth : registry (CLI/MCP)

## Desactiver un bloc

1. CLI :
```bash
php artisan page-content-manager:block:disable hero --force
```

2. Config :
```php
'disabled_blocks' => ['hero'],
```

3. Groupes : ne pas inclure le bloc dans `block_groups`

## Remplacer un bloc core

Creer un bloc custom avec le meme `getType()`.
Le custom remplace le core.

## Cache

```php
'cache' => [
    'enabled' => env('PAGE_CONTENT_MANAGER_CACHE_ENABLED', true),
    'key' => 'page-content-manager.blocks.registry',
    'ttl' => env('PAGE_CONTENT_MANAGER_CACHE_TTL', 3600),
],
```

Invalider :

```bash
php artisan page-content-manager:blocks:clear-cache
```

## Validation

```env
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT=true
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT_THROW=false
```

Ou manuellement :

```bash
php artisan page-content-manager:blocks:validate
```

## Evenements de transformation

- `BlockTransforming` : avant transformation
- `BlockTransformed` : apres transformation

Utiles pour enrichir/normaliser les donnees.

## Facade Blocks

```php
use Xavcha\\PageContentManager\\Facades\\Blocks;

Blocks::get('hero');
Blocks::all();
Blocks::has('text');
Blocks::clearCache();
```
