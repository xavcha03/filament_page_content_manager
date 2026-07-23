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
  "content_mode": "blocks",
  "seo_title": "...",
  "seo_description": "...",
  "robots": null,
  "sections": [
    {
      "type": "hero",
      "data": { "...": "..." }
    }
  ],
  "metadata": {
    "schema_version": 1
  },
  "experience": null
}
```

### Mode Experience

Quand `content_mode` vaut `"experience"` :

```json
{
  "content_mode": "experience",
  "sections": [ "... conserve pour BC, ignorer en frontend ..." ],
  "experience": {
    "key": "home-organic",
    "content": {
      "hero_title": "..."
    }
  }
}
```

Le frontend doit brancher le rendu sur `content_mode` (voir `docs/agent-frontend-experiences.md`).

## Champ `robots`

Expose pour le frontend (meta robots). Valeurs :

- `null` : page indexable (defaut)
- `"noindex"` : page non indexable (`seo_noindex = true` en base)

## Pages supprimees (soft delete)

`GET /api/pages/{slug}` utilise `PageUrlResolver` :

| Situation | HTTP | `resolution` |
|-----------|------|----------------|
| Page publiee active | 200 | (corps page habituel) |
| Slug inconnu | 404 | `not_found` |
| Brouillon / non publie | 404 | `not_found` |
| Corbeille + politique 404 | 404 | `not_found` |
| Corbeille + politique 410 | 410 | `gone` |
| Corbeille + 301 page | 301 | `redirect` + header `Location` |
| Corbeille + 301 URL | 301 | `redirect` + header `Location` |

Exemple redirect :

```json
{
  "resolution": "redirect",
  "message": "Redirection vers une autre page",
  "redirect": {
    "type": "page",
    "slug": "nouvelle-page",
    "location": "/nouvelle-page"
  }
}
```

`GET /api/pages` (liste) exclut les pages en corbeille.

## Prévisualisation (brouillon / planifié)

```
GET /api/pages/{slug}?preview_token={token}
```

- Token signé généré depuis Filament (bouton **Prévisualiser**)
- Réponse 200 avec `preview: true` et `page_status` si le token est valide
- 403 si token invalide ou expiré

Voir `docs/preview-frontend.md` pour l'intégration Next.js.

## Notes

- Le contenu est transforme par `SectionTransformer`.
- La structure exacte des blocs depend du registry.
- Utilisez MCP ou CLI pour decouvrir les schemas.
