# API

## Endpoints

- `GET /api/pages`
- `GET /api/pages/{slug}`

Le prefix est configurable via `route_prefix` dans `config/page-content-manager.php`.

## Reponse (exemple minimal)

```json
{
  "id": 1,
  "title": "Accueil",
  "slug": "home",
  "type": "home",
  "seo_title": "...",
  "seo_description": "...",
  "sections": [
    {
      "type": "hero",
      "data": { "...": "..." }
    }
  ],
  "metadata": {
    "schema_version": 1
  }
}
```

## Notes

- Le contenu est transforme par `SectionTransformer`.
- La structure exacte des blocs depend du registry.
- Utilisez MCP ou CLI pour decouvrir les schemas.
