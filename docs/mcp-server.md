# Serveur MCP - Page Content Manager

## Vue d'ensemble

Ce serveur MCP (Model Context Protocol) permet a des agents IA de lire et gerer les pages et leurs blocs (equivalent des actions Filament).
Le transport est HTTP JSON-RPC 2.0.

## Configuration rapide

```env
PAGE_CONTENT_MANAGER_MCP_ENABLED=true
PAGE_CONTENT_MANAGER_MCP_ROUTE=mcp/pages
PAGE_CONTENT_MANAGER_MCP_AUTO_REGISTER=true
```

Configuration avancee (token recommande) :

```env
PAGE_CONTENT_MANAGER_MCP_TOKEN=change-me
PAGE_CONTENT_MANAGER_MCP_REQUIRE_TOKEN=true
PAGE_CONTENT_MANAGER_MCP_TOKEN_HEADER=X-MCP-Token
```

Equivalent `config/page-content-manager.php` :

```php
'mcp' => [
    'enabled' => true,
    'route' => 'mcp/pages',
    'auto_register' => true,
    'middleware' => [],
    'token' => 'change-me',
    'token_header' => 'X-MCP-Token',
    'require_token' => true,
],
```

## Acces

Endpoint :

```
POST /mcp/pages
```

Le `GET` retourne 405 (MCP HTTP spec).

## Securite (recommandee)

Deux options de token sont supportees :

- Header `X-MCP-Token: <token>` (defaut)
- Header `Authorization: Bearer <token>`

Pour une vraie auth (multi-clients, scopes, revocation), ajoute un middleware Laravel :

```php
'mcp' => [
    'middleware' => ['auth:sanctum'],
],
```

## Format MCP (JSON-RPC 2.0)

Exemple d'initialisation :

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {
    "protocolVersion": "2024-11-05",
    "capabilities": {},
    "clientInfo": {
      "name": "my-client",
      "version": "1.0.0"
    }
  }
}
```

Exemple d'appel d'outil :

```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/call",
  "params": {
    "name": "list_pages",
    "arguments": {}
  }
}
```

## Outils disponibles (pages)

- `list_pages` : liste pages, filtres `status` (draft|scheduled|published|all) et `type` (home|standard|all).
- `get_page_content` : recupere le contenu d'une page. Parametres `id` ou `slug`.
- `create_page` : cree une page. Parametres `title`, `slug`, `type` (standard), `seo_title`, `seo_description`, `status` (draft|published).
- `create_page_with_blocks` : cree une page avec ses blocs en une seule requete. Parametres `title`, `slug`, `type`, `seo_title`, `seo_description`, `status`, `blocks`.
- `update_page` : modifie une page. Parametres `id` ou `slug`, puis champs a modifier (`title`, `slug_new`, `seo_title`, `seo_description`, `status`).
- `duplicate_page` : duplique une page. Parametres `id` ou `slug`, `new_slug`, `new_title`, `status`.
- `delete_page` : supprime une page. Parametres `id` ou `slug`, `confirm=true`.

## Outils disponibles (blocs)

- `list_blocks` : liste tous les blocs disponibles (type, label, schema, etc.).
- `get_block_schema` : details schema d'un bloc. Parametre `type`.
- `add_blocks_to_page` : ajoute un ou plusieurs blocs a une page. Parametres `id` ou `slug`, `blocks`.
- `update_block` : met a jour un bloc par index (0-based). Parametres `page_id` ou `page_slug`, `block_index`, `data`.
- `update_block_fields` : met a jour partiellement un bloc sans ecraser tout `data`. Parametres `page_id` ou `page_slug`, `block_index`, `data`.
- `delete_block` : supprime un bloc par index (0-based). Parametres `page_id` ou `page_slug`, `block_index`.
- `reorder_blocks` : reordonne les blocs. Parametres `page_id` ou `page_slug` + soit `from_index` + `to_index`, soit `new_order`.

## Workflow agent recommande

Pour generer une page complete sans blocs vides :

1. `list_blocks`
2. `get_block_schema` pour chaque bloc utilise
3. `create_page_with_blocks` (ou `create_page` puis `add_blocks_to_page`)
4. `get_page_content` pour verifier
5. `update_block_fields` pour les modifications ponctuelles

## Structure des blocs

Chaque bloc est stocke comme :

```json
{
  "type": "hero",
  "data": { "...": "..." }
}
```

Les indices des blocs sont 0-based (0, 1, 2...).
Utilise `get_page_content` pour obtenir la liste et les indices actuels.

## Medias

Les images doivent etre uploadees via Filament avant. Ensuite tu references les medias via leurs `image_id` (MediaFile ID). Ne pas envoyer d'URL ni de base64 dans MCP.

## Limitations / regles

- `create_page` : type autorise = `standard`.
- `delete_page` : la page `home` ne peut pas etre supprimee.
- Les blocs ne changent pas de type : `update_block` modifie seulement `data`.
- `update_block_fields` fait un merge partiel des champs sur le `data` existant.
- Le contenu est stocke dans `content.sections` + `content.metadata.schema_version`.

## Metadonnees MCP pour blocs custom

Pour enrichir la decouverte MCP, tu peux ajouter des metadonnees a un bloc custom via le trait `HasMcpMetadata`.
Cela permet d'exposer une description, des champs et des exemples plus precis aux agents.

## Exemples rapides

Lister les pages :

```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "list_pages",
    "arguments": { "status": "all", "type": "all" }
  }
}
```

Ajouter un bloc `hero` :

```json
{
  "jsonrpc": "2.0",
  "id": 4,
  "method": "tools/call",
  "params": {
    "name": "add_blocks_to_page",
    "arguments": {
      "slug": "home",
      "blocks": [
        {
          "type": "hero",
          "data": {
            "titre": "Bienvenue",
            "description": "Intro courte",
            "variant": "hero",
            "image_id": 123
          }
        }
      ]
    }
  }
}
```

Modifier seulement le titre du hero (index 0) :

```json
{
  "jsonrpc": "2.0",
  "id": 6,
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

Reordonner les blocs :

```json
{
  "jsonrpc": "2.0",
  "id": 5,
  "method": "tools/call",
  "params": {
    "name": "reorder_blocks",
    "arguments": {
      "page_slug": "home",
      "from_index": 0,
      "to_index": 2
    }
  }
}
```

## Liens utiles

- `docs/mcp-media-management.md` (proposition future, upload medias non implemente)
- `docs/mcp-test-simple.md` (scripts de test simples)
