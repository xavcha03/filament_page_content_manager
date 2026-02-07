# Test simple MCP

## Prerequis

- URL MCP : `https://votre-domaine.com/mcp/pages`
- Token si active : `PAGE_CONTENT_MANAGER_MCP_TOKEN`

## Exemple curl

```bash
curl -X POST https://votre-domaine.com/mcp/pages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-MCP-Token: change-me" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/call",
    "params": {
      "name": "list_pages",
      "arguments": {}
    }
  }'
```

## Conseils

- Utiliser `list_blocks` puis `get_block_schema` avant d'ecrire.
- Utiliser `update_block_fields` pour modifier un champ sans tout ecraser.
