# Test simple du serveur MCP

## 🌐 Solution la plus simple : Utiliser directement l'URL

Votre serveur MCP est accessible directement via HTTP POST :

**URL :** `https://xavcha-pages.ddev.site/mcp/pages`

## 🧪 Test avec curl (le plus simple)

### Lister les outils disponibles

```bash
curl -X POST https://xavcha-pages.ddev.site/mcp/pages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -k \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/list"
  }'
```

### Créer une page

```bash
curl -X POST https://xavcha-pages.ddev.site/mcp/pages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -k \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/call",
    "params": {
      "name": "create_page",
      "arguments": {
        "title": "Ma page test",
        "slug": "ma-page-test",
        "status": "draft"
      }
    }
  }'
```

### Lister les pages

```bash
curl -X POST https://xavcha-pages.ddev.site/mcp/pages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -k \
  -d '{
    "jsonrpc": "2.0",
    "id": 3,
    "method": "tools/call",
    "params": {
      "name": "list_pages",
      "arguments": {}
    }
  }'
```

### Lister les blocs disponibles

```bash
curl -X POST https://xavcha-pages.ddev.site/mcp/pages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -k \
  -d '{
    "jsonrpc": "2.0",
    "id": 4,
    "method": "tools/call",
    "params": {
      "name": "list_blocks",
      "arguments": {}
    }
  }'
```

## 🌍 Test avec un client HTTP en ligne

Vous pouvez utiliser n'importe quel client HTTP en ligne pour tester :

1. **Postman** : https://www.postman.com/
2. **Insomnia** : https://insomnia.rest/
3. **HTTPie Web** : https://httpie.io/app
4. **REST Client (VS Code)** : Extension pour VS Code

### Configuration dans Postman/Insomnia

- **Method** : POST
- **URL** : `https://xavcha-pages.ddev.site/mcp/pages`
- **Headers** :
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Body** (raw JSON) :
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/list"
}
```

## 🚀 Solution encore plus simple : Script shell

Créez un fichier `test-mcp.sh` :

```bash
#!/bin/bash

URL="https://xavcha-pages.ddev.site/mcp/pages"

# Fonction pour appeler le serveur MCP
mcp_call() {
  local method=$1
  local params=$2
  
  curl -s -X POST "$URL" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -k \
    -d "{
      \"jsonrpc\": \"2.0\",
      \"id\": $(date +%s),
      \"method\": \"$method\",
      \"params\": $params
    }" | python3 -m json.tool
}

# Exemples d'utilisation
echo "=== Liste des outils ==="
mcp_call "tools/list" "{}"

echo ""
echo "=== Liste des pages ==="
mcp_call "tools/call" '{"name": "list_pages", "arguments": {}}'

echo ""
echo "=== Liste des blocs ==="
mcp_call "tools/call" '{"name": "list_blocks", "arguments": {}}'
```

Utilisation :
```bash
chmod +x test-mcp.sh
./test-mcp.sh
```

## 📱 Solution la plus simple : Interface web simple

Vous pouvez créer une page HTML simple pour tester le serveur MCP directement dans votre navigateur (mais nécessite un serveur pour éviter les problèmes CORS).

## ✅ Vérification rapide

Le serveur fonctionne si cette commande retourne une liste d'outils :

```bash
curl -s -X POST https://xavcha-pages.ddev.site/mcp/pages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -k \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' \
  | grep -o '"name":"[^"]*"' | head -5
```

Vous devriez voir :
- `create_page`
- `update_page`
- `list_pages`
- `list_blocks`
- `add_blocks_to_page`

