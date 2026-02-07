# MCP Server (Backend)

## Vue d'ensemble

Serveur MCP (HTTP JSON-RPC 2.0) pour lire/gerer les pages et blocs.

## Configuration

```env
PAGE_CONTENT_MANAGER_MCP_ENABLED=true
PAGE_CONTENT_MANAGER_MCP_ROUTE=mcp/pages
PAGE_CONTENT_MANAGER_MCP_AUTO_REGISTER=true

PAGE_CONTENT_MANAGER_MCP_TOKEN=change-me
PAGE_CONTENT_MANAGER_MCP_REQUIRE_TOKEN=true
PAGE_CONTENT_MANAGER_MCP_TOKEN_HEADER=X-MCP-Token

# Optional: expose menu tools from this package MCP server
PAGE_CONTENT_MANAGER_MENU_MCP_ENABLED=true
PAGE_CONTENT_MANAGER_MENU_LINKS_STORE=App\\Menu\\MainMenuLinksStore
```

## Acces

```
POST /mcp/pages
```

## Securite (recommandee)

- Header `X-MCP-Token: <token>`
- Ou `Authorization: Bearer <token>`

Middleware Laravel :

```php
'mcp' => [
    'middleware' => ['auth:sanctum'],
],
```

## Regle IA

Ne jamais supposer les blocs disponibles.
Toujours : `list_blocks` puis `get_block_schema`.

## Outils disponibles (pages)

- `list_pages`
- `get_page_content`
- `create_page`
- `create_page_with_blocks`
- `update_page`
- `duplicate_page`
- `delete_page`

## Outils disponibles (blocs)

- `list_blocks`
- `get_block_schema`
- `add_blocks_to_page`
- `update_block`
- `update_block_fields`
- `delete_block`
- `reorder_blocks`

## Outils disponibles (menu, optionnels)

- `list_main_menu`
- `get_main_menu`
- `add_main_menu_link`
- `upsert_main_menu_link`
- `update_main_menu_link`
- `delete_main_menu_link`
- `reorder_main_menu_links`
- `move_main_menu_link`
- `replace_main_menu_links`

Activer avec `PAGE_CONTENT_MANAGER_MENU_MCP_ENABLED=true` et fournir une classe
`PAGE_CONTENT_MANAGER_MENU_LINKS_STORE` qui implemente :

`Xavcha\PageContentManager\Menu\Contracts\MenuLinksStore`

## Structure des blocs

```json
{
  "type": "hero",
  "data": { "...": "..." }
}
```

Indices 0-based.

## Medias

Les images doivent etre uploadees via Filament, puis referencees par ID (ex: `image_id`).

## Limitations

- `create_page` : type `standard` uniquement
- `delete_page` : la page `home` ne peut pas etre supprimee
- `update_block_fields` fait un merge partiel du `data`

## Workflow agent recommande

1. `list_blocks`
2. `get_block_schema`
3. `create_page_with_blocks` (ou `create_page` + `add_blocks_to_page`)
4. `get_page_content`
5. `update_block_fields` pour les petites modifs

## Exemples rapides

Modifier seulement le titre du hero (index 0) :

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "update_block_fields",
    "arguments": {
      "page_slug": "home",
      "block_index": 0,
      "data": {
        "titre": "Nouveau titre hero"
      }
    }
  }
}
```

## Liens utiles

- `docs/agent-guide.md`
- `docs/mcp-test-simple.md`
- `docs/mcp-media-management.md` (future)
