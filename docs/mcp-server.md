# Serveur MCP - Page Content Manager

## 📋 Vue d'ensemble

Le serveur MCP (Model Context Protocol) permet aux agents IA (Claude, ChatGPT, etc.) de créer et gérer des pages dans votre application Laravel via le protocole MCP.

## 🔧 Configuration

Le serveur MCP est activé par défaut. Vous pouvez le configurer dans votre fichier `.env` :

```env
PAGE_CONTENT_MANAGER_MCP_ENABLED=true
PAGE_CONTENT_MANAGER_MCP_ROUTE=mcp/pages
```

Ou dans `config/page-content-manager.php` :

```php
'mcp' => [
    'enabled' => true,
    'route' => 'mcp/pages',
],
```

## 🌐 Accès au serveur

Une fois le package installé dans votre application Laravel, le serveur MCP est accessible via HTTP POST sur :

```
POST /mcp/pages
```

### Exemple avec curl

```bash
# Initialiser la connexion MCP
curl -X POST https://votre-domaine.com/mcp/pages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "initialize",
    "params": {
      "protocolVersion": "2024-11-05",
      "capabilities": {},
      "clientInfo": {
        "name": "test-client",
        "version": "1.0.0"
      }
    }
  }'
```

## 🛠️ Outils disponibles

### 1. create_page

Crée une nouvelle page vierge.

**Paramètres** :
- `title` (requis) : Le titre de la page
- `slug` (requis) : L'URL slug (doit être unique)
- `type` (optionnel) : Type de page (`standard` uniquement, par défaut)
- `seo_title` (optionnel) : Titre SEO
- `seo_description` (optionnel) : Description SEO
- `status` (optionnel) : Statut (`draft` ou `published`, par défaut `draft`)

**Exemple** :
```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/call",
  "params": {
    "name": "create_page",
    "arguments": {
      "title": "Ma nouvelle page",
      "slug": "ma-nouvelle-page",
      "status": "draft"
    }
  }
}
```

### 2. update_page

Met à jour une page existante.

**Paramètres** :
- `id` ou `slug` (requis) : Identifiant de la page
- `title` (optionnel) : Nouveau titre
- `slug_new` (optionnel) : Nouveau slug
- `seo_title` (optionnel) : Nouveau titre SEO
- `seo_description` (optionnel) : Nouvelle description SEO
- `status` (optionnel) : Nouveau statut

**Exemple** :
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "update_page",
    "arguments": {
      "slug": "ma-nouvelle-page",
      "title": "Page mise à jour",
      "status": "published"
    }
  }
}
```

### 3. list_pages

Liste toutes les pages avec filtres optionnels.

**Paramètres** :
- `status` (optionnel) : Filtrer par statut (`draft`, `scheduled`, `published`, `all`)
- `type` (optionnel) : Filtrer par type (`home`, `standard`, `all`)

**Exemple** :
```json
{
  "jsonrpc": "2.0",
  "id": 4,
  "method": "tools/call",
  "params": {
    "name": "list_pages",
    "arguments": {
      "status": "published",
      "type": "standard"
    }
  }
}
```

### 4. list_blocks

Liste tous les blocs de contenu disponibles pour construire des pages.

**Paramètres** : Aucun

**Exemple** :
```json
{
  "jsonrpc": "2.0",
  "id": 5,
  "method": "tools/call",
  "params": {
    "name": "list_blocks",
    "arguments": {}
  }
}
```

**Réponse** :
```json
{
  "success": true,
  "blocks": [
    {
      "type": "text",
      "class": "Xavcha\\PageContentManager\\Blocks\\Core\\TextBlock",
      "description": "Texte",
      "fields": [
        {
          "name": "titre",
          "label": "Titre",
          "type": "string",
          "required": false,
          "description": "Le titre du bloc de texte",
          "max_length": 200
        },
        {
          "name": "content",
          "label": "Contenu",
          "type": "string",
          "required": true,
          "description": "Le contenu du bloc (format HTML/rich text)"
        }
      ],
      "mcp_example": {
        "titre": "Titre de la section",
        "content": "<p>Contenu de la section avec du texte formaté.</p>"
      }
    }
  ],
  "count": 14
}
```

### 5. add_blocks_to_page

Ajoute un ou plusieurs blocs de contenu à une page existante.

**Paramètres** :
- `id` ou `slug` (requis) : Identifiant de la page
- `blocks` (requis) : Tableau de blocs à ajouter. Chaque bloc doit avoir :
  - `type` : Le type du bloc (ex: `text`, `hero`)
  - `data` : Les données du bloc selon le schéma du bloc

**Exemple** :
```json
{
  "jsonrpc": "2.0",
  "id": 6,
  "method": "tools/call",
  "params": {
    "name": "add_blocks_to_page",
    "arguments": {
      "slug": "ma-nouvelle-page",
      "blocks": [
        {
          "type": "hero",
          "data": {
            "titre": "Bienvenue sur notre site",
            "description": "Découvrez nos services",
            "variant": "hero",
            "bouton_principal": {
              "texte": "En savoir plus",
              "lien": "/contact"
            }
          }
        },
        {
          "type": "text",
          "data": {
            "titre": "Section de contenu",
            "content": "<p>Ceci est une section de texte.</p>"
          }
        }
      ]
    }
  }
}
```

## 🔍 Lister les outils disponibles

Pour voir tous les outils disponibles :

```json
{
  "jsonrpc": "2.0",
  "id": 5,
  "method": "tools/list"
}
```

## 🔐 Sécurité

- Les pages Home ne peuvent pas être créées ou modifiées via MCP
- La validation des données est effectuée pour tous les paramètres
- L'unicité des slugs est vérifiée automatiquement
- Les erreurs sont gérées de manière sécurisée

## 🧪 Test avec MCP Inspector

Vous pouvez utiliser le MCP Inspector pour tester le serveur :

```bash
npx @modelcontextprotocol/inspector
```

Puis connectez-vous à votre serveur MCP via HTTP.

## 📝 Notes importantes

1. Le serveur MCP utilise le protocole JSON-RPC 2.0
2. Toutes les requêtes doivent être en POST
3. Le header `Content-Type: application/json` est requis
4. Le header `Accept: application/json` est requis
5. Les pages créées via MCP sont créées avec un contenu vide (sections vides)

## 🎨 Métadonnées MCP pour les blocs

Pour que vos blocs personnalisés soient correctement découverts par l'IA via MCP, vous pouvez utiliser le trait `HasMcpMetadata` :

```php
<?php

namespace App\Blocks\Custom;

use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class MonBloc implements BlockInterface
{
    use HasMcpMetadata;

    // ... autres méthodes ...

    /**
     * Retourne les champs du bloc pour MCP.
     */
    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'titre',
                'label' => 'Titre',
                'type' => 'string',
                'required' => true,
                'description' => 'Le titre du bloc',
                'max_length' => 200,
            ],
        ];
    }

    /**
     * Retourne un exemple de données pour le bloc.
     */
    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Exemple de titre',
        ];
    }
}
```

Le trait `HasMcpMetadata` est optionnel et n'est pas requis pour que les blocs fonctionnent. Il permet simplement de fournir des informations supplémentaires à l'IA pour mieux comprendre les blocs disponibles.

**Note** : Les blocs créés via la commande `make-block` incluent automatiquement le trait `HasMcpMetadata` avec des méthodes de base que vous pouvez personnaliser.

## 🚀 Workflow complet : Créer une page avec des blocs

Voici un exemple complet de création d'une page avec des blocs via MCP :

1. **Lister les blocs disponibles** :
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "list_blocks",
    "arguments": {}
  }
}
```

2. **Créer une page** :
```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/call",
  "params": {
    "name": "create_page",
    "arguments": {
      "title": "Ma page",
      "slug": "ma-page",
      "status": "draft"
    }
  }
}
```

3. **Ajouter des blocs** :
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "add_blocks_to_page",
    "arguments": {
      "slug": "ma-page",
      "blocks": [
        {
          "type": "hero",
          "data": { /* données du bloc */ }
        },
        {
          "type": "text",
          "data": { /* données du bloc */ }
        }
      ]
    }
  }
}
```

4. **Publier la page** :
```json
{
  "jsonrpc": "2.0",
  "id": 4,
  "method": "tools/call",
  "params": {
    "name": "update_page",
    "arguments": {
      "slug": "ma-page",
      "status": "published"
    }
  }
}
```

