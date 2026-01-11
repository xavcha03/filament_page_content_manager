# Configuration Gemini CLI pour le serveur MCP

## Configuration pour serveur HTTP MCP

Pour connecter Gemini CLI à votre serveur MCP HTTP, vous devez configurer le serveur dans le fichier de configuration de Gemini CLI.

### Format de configuration

Dans votre fichier de configuration Gemini CLI (généralement `~/.gemini/settings.json` ou similaire), ajoutez :

```json
{
  "mcpServers": {
    "xavcha-pages": {
      "url": "https://xavcha-pages.ddev.site/mcp/pages",
      "transport": "http",
      "headers": {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      "verifySSL": false
    }
  }
}
```

### Points importants

1. **`verifySSL: false`** : Nécessaire car DDEV utilise des certificats SSL auto-signés
2. **`transport: "http"`** : Indique que c'est un serveur HTTP (pas stdio)
3. **`url`** : L'URL complète du serveur MCP

### Alternative : Utiliser un tunnel local

Si vous préférez éviter de désactiver la vérification SSL, vous pouvez utiliser un tunnel local :

```json
{
  "mcpServers": {
    "xavcha-pages": {
      "url": "http://localhost:8080/mcp/pages",
      "transport": "http",
      "headers": {
        "Content-Type": "application/json",
        "Accept": "application/json"
      }
    }
  }
}
```

Puis créer un tunnel avec :
```bash
ddev share --tunnel-url=xavcha-pages.ddev.site
```

### Vérification

Après avoir configuré, redémarrez Gemini CLI et vérifiez avec :
```
/mcp
```

Le serveur devrait apparaître comme connecté (🟢).

