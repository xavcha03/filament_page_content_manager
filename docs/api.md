# Documentation API

## Endpoints

### Liste des pages

Récupère la liste de toutes les pages publiées.

**Endpoint:** `GET /api/pages`

**Réponse:**

```json
{
  "pages": [
    {
      "id": 1,
      "title": "Accueil",
      "slug": "home",
      "type": "home"
    },
    {
      "id": 2,
      "title": "Contact",
      "slug": "contact",
      "type": "standard"
    }
  ]
}
```

### Récupérer une page

Récupère une page par son slug avec son contenu transformé.

**Endpoint:** `GET /api/pages/{slug}`

**Paramètres:**
- `slug` (string) : Le slug de la page. Utilisez `home` ou laissez vide pour la page d'accueil.

**Réponse:**

```json
{
  "id": 1,
  "title": "Accueil",
  "slug": "home",
  "type": "home",
  "seo_title": "Page d'accueil",
  "seo_description": "Description SEO",
  "sections": [
    {
      "type": "hero",
      "data": {
        "type": "hero",
        "titre": "Bienvenue",
        "description": "Description du hero",
        "variant": "hero",
        "image_fond": "https://example.com/image.jpg",
        "image_fond_alt": "Image de fond",
        "bouton_principal": {
          "texte": "En savoir plus",
          "lien": "/about"
        }
      }
    },
    {
      "type": "text",
      "data": {
        "type": "text",
        "titre": "Titre du texte",
        "content": "Contenu du texte"
      }
    }
  ],
  "metadata": {
    "schema_version": 1
  }
}
```

**Erreurs:**

- `404` : Page non trouvée

```json
{
  "message": "Page non trouvée"
}
```

## Structure des sections

Chaque section dans `sections` a la structure suivante :

```json
{
  "type": "nom_du_bloc",
  "data": {
    // Données transformées du bloc
  }
}
```

## Types de blocs

### Hero

```json
{
  "type": "hero",
  "data": {
    "titre": "Titre principal",
    "description": "Description",
    "variant": "hero" | "projects",
    "image_fond": "URL de l'image",
    "image_fond_alt": "Texte alternatif",
    "images": ["URL1", "URL2"], // Pour variant "projects"
    "bouton_principal": {
      "texte": "Texte du bouton",
      "lien": "URL ou chemin"
    }
  }
}
```

### Text

```json
{
  "type": "text",
  "data": {
    "titre": "Titre (optionnel)",
    "content": "Contenu du texte"
  }
}
```

### Image

```json
{
  "type": "image",
  "data": {
    "image_url": "URL de l'image",
    "alt": "Texte alternatif",
    "caption": "Légende"
  }
}
```

### Gallery

```json
{
  "type": "gallery",
  "data": {
    "titre": "Titre de la galerie",
    "images": ["URL1", "URL2", "URL3"]
  }
}
```

### CTA

```json
{
  "type": "cta",
  "data": {
    "titre": "Titre",
    "description": "Description",
    "variant": "simple" | "hero" | "subscription",
    "cta_text": "Texte du bouton",
    "cta_link": "URL du bouton",
    "background_image": "URL (pour variant hero)",
    "phone_number": "Numéro (pour variant hero)",
    "secondary_cta_text": "Texte bouton secondaire (pour variant subscription)"
  }
}
```

### FAQ

```json
{
  "type": "faq",
  "data": {
    "titre": "Titre de la section FAQ",
    "faqs": [
      {
        "question": "Question 1",
        "answer": "Réponse 1"
      },
      {
        "question": "Question 2",
        "answer": "Réponse 2"
      }
    ]
  }
}
```

### Contact Form

```json
{
  "type": "contact_form",
  "data": {
    "titre": "Contactez-nous",
    "description": "Description",
    "success_message": "Message de confirmation"
  }
}
```

## Configuration des routes

Les routes peuvent être configurées dans `config/page-content-manager.php` :

```php
'routes' => true,
'route_prefix' => 'api',
'route_middleware' => ['api'],
```

Pour désactiver les routes :

```php
'routes' => false,
```

## Transformation des blocs

Les blocs sont automatiquement transformés via leur méthode `transform()` définie dans chaque bloc.

Les blocs sont auto-découverts dans :
- Package : `src/Blocks/Core/`
- Application : `app/Blocks/Custom/`

Si un bloc n'a pas de méthode `transform()`, les données brutes sont retournées.

### Événements de transformation

Le package expose deux événements Laravel pour personnaliser la transformation :

- **`BlockTransforming`** : Déclenché avant la transformation, permet de modifier les données brutes
- **`BlockTransformed`** : Déclenché après la transformation, permet de modifier les données transformées

**Exemple** :

```php
use Xavcha\PageContentManager\Events\BlockTransformed;
use Illuminate\Support\Facades\Event;

Event::listen(BlockTransformed::class, function (BlockTransformed $event) {
    // Enrichir les données transformées
    $transformedData = $event->getTransformedData();
    $transformedData['metadata'] = ['transformed_at' => now()];
    $event->setTransformedData($transformedData);
});
```

Voir [README.md](../README.md#événements-pour-personnaliser-la-transformation) pour plus de détails et d'exemples.

