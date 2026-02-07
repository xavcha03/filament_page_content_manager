# Xavcha Page Content Manager

Package Laravel Filament pour gerer des pages avec un systeme de blocs de contenu simple, flexible et reutilisable.

> Version 0.2.4 (pre-v1). L'API peut evoluer.

## Objectif

Fournir un "mini CMS" propre et rapide a integrer, avec :
- Des pages Filament (titre, slug, SEO, statut)
- Un systeme de blocs modulaires
- Une API pour servir le contenu transforme
- Un serveur MCP pour agents IA

## Demarrage rapide

```bash
composer require xavcha/page-content-manager
php artisan vendor:publish --tag=page-content-manager-config
php artisan migrate
```

Enregistrer la ressource Filament dans votre `PanelProvider` :

```php
use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;

public function panel(Panel $panel): Panel
{
    return $panel->resources([
        PageResource::class,
    ]);
}
```

## Concepts cles

- **Blocs auto-decouverts** :
  - Core : `src/Blocks/Core/`
  - Custom : `app/Blocks/Custom/`
- **Bloc core Tarifs** : disponible via le type `tarifs` pour afficher des plans de prix (nom, prix, periode, points inclus, mise en avant, CTA).
- **Source of truth** : toujours le registry (CLI ou MCP), jamais une liste statique.
- **Desactivation** : via `disabled_blocks` ou `block_groups` dans `config/page-content-manager.php`.

## Pour agents IA

Ne jamais supposer les blocs disponibles. Toujours :
1. `list_blocks`
2. `get_block_schema`
3. ecrire/mettre a jour les blocs

Doc complete : `docs/agent-guide.md`.

## Documentation

- `docs/installation.md`
- `docs/usage.md`
- `docs/blocks-architecture.md`
- `docs/custom-blocks.md`
- `docs/api.md`
- `docs/mcp-server.md`
- `docs/reusable-system.md`
- `docs/testing.md`

## Licence

MIT
