# MCP Menu Tools (Package)

## Objectif

Permettre au package `xavcha/page-content-manager` d'exposer des outils MCP de gestion de menu, sans imposer un stockage fixe.

## Activation

Dans l'application hote :

```env
PAGE_CONTENT_MANAGER_MENU_MCP_ENABLED=true
PAGE_CONTENT_MANAGER_MENU_LINKS_STORE=App\Menu\MainMenuLinksStore
```

## Contrat de stockage

La classe configuree doit implementer :

`Xavcha\PageContentManager\Menu\Contracts\MenuLinksStore`

```php
<?php

namespace App\Menu;

use App\Settings\MainMenuSettings;
use Xavcha\PageContentManager\Menu\Contracts\MenuLinksStore;

class MainMenuLinksStore implements MenuLinksStore
{
    public function getLinks(): array
    {
        return app(MainMenuSettings::class)->links ?? [];
    }

    public function saveLinks(array $links): void
    {
        $settings = app(MainMenuSettings::class);
        $settings->links = $links;
        $settings->save();
    }
}
```

## Outils exposes

- `list_main_menu`
- `get_main_menu`
- `add_main_menu_link`
- `upsert_main_menu_link`
- `update_main_menu_link`
- `delete_main_menu_link`
- `reorder_main_menu_links`
- `move_main_menu_link`
- `replace_main_menu_links`

