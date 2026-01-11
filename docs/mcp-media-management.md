# Gestion des Médias via MCP - Proposition d'implémentation

## 📋 Vue d'ensemble

Ce document décrit une proposition d'implémentation pour la gestion des médias (images, vidéos, fichiers) via le serveur MCP. Cette fonctionnalité n'est **pas encore implémentée** mais représente une amélioration future importante.

## 🎯 Objectifs

Permettre aux agents IA de :
- Uploader des médias directement via MCP
- Lister les médias disponibles
- Attacher des médias aux blocs de contenu
- Gérer les médias existants

## 🔧 Défis techniques

### 1. Upload de fichiers

**Problème** : Le protocole MCP JSON-RPC n'est pas optimisé pour l'upload de fichiers binaires.

**Solutions possibles** :

#### Option A : Base64 Encoding
```json
{
  "name": "upload_media",
  "arguments": {
    "filename": "image.jpg",
    "content": "base64_encoded_string",
    "mime_type": "image/jpeg"
  }
}
```
**Avantages** : Simple, compatible avec JSON-RPC
**Inconvénients** : Taille limitée, overhead de ~33%, pas optimal pour gros fichiers

#### Option B : Endpoint HTTP séparé
Créer un endpoint HTTP dédié pour l'upload :
```
POST /mcp/pages/media/upload
Content-Type: multipart/form-data
```
**Avantages** : Efficace, supporte les gros fichiers
**Inconvénients** : Nécessite une authentification séparée, sort du protocole MCP standard

#### Option C : URL externe
L'agent IA upload via un service externe (Cloudinary, S3, etc.) et fournit l'URL :
```json
{
  "name": "attach_media",
  "arguments": {
    "url": "https://example.com/image.jpg",
    "block_index": 0,
    "field": "image"
  }
}
```
**Avantages** : Simple, pas de gestion de stockage
**Inconvénients** : Dépendance externe, pas de contrôle sur les médias

### 2. Intégration avec la Media Library

Le package utilise `xavcha/fillament-xavcha-media-library`. Il faudra :

1. **Créer un modèle Media** si ce n'est pas déjà fait
2. **Créer des endpoints MCP** pour interagir avec la media library
3. **Gérer les relations** entre médias et blocs

### 3. Validation et sécurité

- **Types de fichiers autorisés** : images, vidéos, PDFs ?
- **Taille maximale** : limiter la taille des uploads
- **Authentification** : qui peut uploader ?
- **Validation MIME type** : vérifier que le type correspond au contenu

## 🛠️ Implémentation proposée

### Outils MCP à créer

#### 1. `list_media`
Liste les médias disponibles dans la bibliothèque.

```json
{
  "name": "list_media",
  "arguments": {
    "type": "image",  // optional: "image", "video", "document", "all"
    "limit": 50,
    "offset": 0
  }
}
```

**Réponse** :
```json
{
  "success": true,
  "media": [
    {
      "id": 1,
      "filename": "hero-image.jpg",
      "url": "https://example.com/storage/media/hero-image.jpg",
      "mime_type": "image/jpeg",
      "size": 245678,
      "created_at": "2025-01-11T10:00:00Z"
    }
  ],
  "total": 150
}
```

#### 2. `upload_media` (Option A - Base64)
Upload un média via Base64.

```json
{
  "name": "upload_media",
  "arguments": {
    "filename": "my-image.jpg",
    "content": "iVBORw0KGgoAAAANSUhEUgAA...",
    "mime_type": "image/jpeg",
    "alt_text": "Description de l'image"
  }
}
```

**Réponse** :
```json
{
  "success": true,
  "media": {
    "id": 123,
    "filename": "my-image.jpg",
    "url": "https://example.com/storage/media/my-image.jpg",
    "mime_type": "image/jpeg",
    "size": 245678
  }
}
```

#### 3. `attach_media_to_block`
Attache un média existant à un bloc.

```json
{
  "name": "attach_media_to_block",
  "arguments": {
    "page_id": 6,
    "block_index": 0,
    "field": "image",  // nom du champ dans le bloc
    "media_id": 123
  }
}
```

#### 4. `get_media_info`
Récupère les informations d'un média.

```json
{
  "name": "get_media_info",
  "arguments": {
    "media_id": 123
  }
}
```

#### 5. `delete_media`
Supprime un média (avec vérification des références).

```json
{
  "name": "delete_media",
  "arguments": {
    "media_id": 123,
    "confirm": true
  }
}
```

### Structure de données

#### Modèle Media (à créer si nécessaire)
```php
class Media extends Model
{
    protected $fillable = [
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
        'url',
        'alt_text',
        'description',
    ];
}
```

### Intégration avec les blocs

Les blocs qui utilisent des médias devront :
1. Stocker l'ID du média dans leurs données
2. Utiliser un transformer pour convertir l'ID en URL

Exemple pour un bloc Image :
```php
public static function transform(array $data): array
{
    $mediaId = $data['media_id'] ?? null;
    $mediaUrl = null;
    
    if ($mediaId) {
        $media = Media::find($mediaId);
        $mediaUrl = $media ? $media->url : null;
    }
    
    return [
        'type' => 'image',
        'url' => $mediaUrl,
        'alt' => $data['alt'] ?? '',
    ];
}
```

## 📝 Recommandations

### Phase 1 : MVP (Minimum Viable Product)
1. **`list_media`** - Lister les médias existants
2. **`attach_media_to_block`** - Utiliser des médias déjà uploadés via l'admin
3. **`get_media_info`** - Informations sur un média

**Justification** : Permet d'utiliser les médias sans gérer l'upload complexe.

### Phase 2 : Upload basique
4. **`upload_media`** - Upload via Base64 (limité à 5-10MB)

**Justification** : Permet l'upload pour petits fichiers sans infrastructure supplémentaire.

### Phase 3 : Upload avancé (si nécessaire)
5. Endpoint HTTP séparé pour gros fichiers
6. Intégration avec services cloud (S3, Cloudinary)

## ⚠️ Limitations connues

1. **Base64 overhead** : ~33% de taille supplémentaire
2. **Taille limitée** : JSON-RPC a des limites de taille de payload
3. **Performance** : Upload de gros fichiers peut être lent
4. **Sécurité** : Validation stricte nécessaire pour éviter les abus

## 🔐 Sécurité

### Mesures à implémenter

1. **Validation des types MIME** : Vérifier que le type correspond au contenu réel
2. **Limite de taille** : Limiter à 10MB par défaut (configurable)
3. **Types autorisés** : Whitelist des types MIME acceptés
4. **Scan antivirus** : Optionnel mais recommandé pour les uploads
5. **Authentification** : Vérifier que l'utilisateur a les droits d'upload

### Configuration proposée

```php
// config/page-content-manager.php
'mcp' => [
    'media' => [
        'upload_enabled' => true,
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'video/mp4',
            'application/pdf',
        ],
        'storage_disk' => 'public',
    ],
],
```

## 📚 Références

- [Laravel File Storage](https://laravel.com/docs/filesystem)
- [MCP Protocol Specification](https://modelcontextprotocol.io)
- [JSON-RPC 2.0 Specification](https://www.jsonrpc.org/specification)

## 🎯 Conclusion

La gestion des médias via MCP est une fonctionnalité complexe qui nécessite des compromis. La recommandation est de commencer par la Phase 1 (liste et attachement) qui permet déjà une grande partie des cas d'usage sans la complexité de l'upload.

L'upload via Base64 peut être ajouté en Phase 2 pour les petits fichiers, et une solution plus robuste (endpoint HTTP séparé) peut être envisagée en Phase 3 si nécessaire.
