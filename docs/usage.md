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

Le package inclut 7 blocs core par défaut :

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

### Gérer les blocs avec le CLI

Vous pouvez gérer vos blocs via les commandes CLI :

```bash
# Lister tous les blocs
php artisan page-content-manager:block:list

# Créer un nouveau bloc
php artisan page-content-manager:make-block

# Inspecter un bloc
php artisan page-content-manager:block:inspect hero

# Voir les statistiques
php artisan page-content-manager:blocks:stats

# Valider tous les blocs
php artisan page-content-manager:blocks:validate
```

Voir [README.md](../README.md#cli-interactif-pour-la-gestion-des-blocs) pour la documentation complète.

### Organiser les blocs en groupes

Pour définir l'ordre d'affichage des blocs et créer des groupes pour différentes ressources :

1. **Publier la configuration** :
```bash
php artisan vendor:publish --tag=page-content-manager-config
```

2. **Configurer les groupes dans `config/page-content-manager.php`** :
```php
'block_groups' => [
    'pages' => [
        'blocks' => [
            \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
            \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
            // ... dans l'ordre souhaité
        ],
    ],
],
```

3. **Utiliser dans vos ressources** :
```php
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;

ContentTab::make('pages') // Utilise le groupe 'pages'
```

Voir [Architecture des blocs](blocks-architecture.md#groupes-de-blocs-et-ordre-personnalisé) pour plus de détails.

### Validation des blocs au démarrage

Pour détecter les erreurs dans vos blocs dès le démarrage de l'application, activez la validation automatique dans votre `.env` :

```env
PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT=true
```

Voir [Architecture des blocs](blocks-architecture.md#validation-des-blocs) pour plus de détails.

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



