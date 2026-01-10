# Guide d'utilisation

## Gérer les pages

### Créer une page

1. Accédez à **Pages** dans votre panel Filament
2. Cliquez sur **Nouvelle page**
3. Remplissez les informations :
   - **Titre** : Le titre de la page
   - **Slug** : L'URL de la page (généré automatiquement depuis le titre)
   - **Type** : Home ou Standard
   - **Statut** : Brouillon, Planifié ou Publié
4. Dans l'onglet **SEO**, ajoutez les métadonnées SEO
5. Dans l'onglet **Contenu**, ajoutez des sections avec les blocs disponibles

### Types de pages

- **Home** : Page d'accueil (une seule peut exister)
- **Standard** : Pages normales

### Statuts

- **Brouillon** : Page non publiée
- **Planifié** : Page qui sera publiée à une date future
- **Publié** : Page visible publiquement

### Blocs disponibles

#### Hero
Section hero avec image de fond ou galerie d'images.

#### Text
Bloc de texte simple avec titre et contenu.

#### Image
Image unique avec texte alternatif et légende.

#### Gallery
Galerie d'images avec titre.

#### CTA (Call to Action)
Bloc d'appel à l'action avec variantes (simple, hero, subscription).

#### FAQ
Section de questions fréquentes avec titre et liste de Q/A.

#### Contact Form
Formulaire de contact avec titre, description et message de confirmation.

## Utiliser l'API

### Liste des pages

```bash
GET /api/pages
```

Réponse :

```json
{
  "pages": [
    {
      "id": 1,
      "title": "Accueil",
      "slug": "home",
      "type": "home"
    }
  ]
}
```

### Récupérer une page

```bash
GET /api/pages/{slug}
```

Exemple : `GET /api/pages/contact`

Réponse :

```json
{
  "id": 2,
  "title": "Contact",
  "slug": "contact",
  "type": "standard",
  "seo_title": "Contactez-nous",
  "seo_description": "Description SEO",
  "sections": [
    {
      "type": "hero",
      "data": {
        "titre": "Contactez-nous",
        "description": "...",
        "variant": "hero",
        "image_fond": "https://..."
      }
    }
  ],
  "metadata": {
    "schema_version": 1
  }
}
```



