# Système réutilisable pour autres ressources

Le package permet d'ajouter facilement les fonctionnalités SEO et Content à n'importe quelle ressource Filament.

## Cas d'usage : Style de danse

Supposons que vous avez une ressource `DanceStyle` et que vous voulez ajouter une page de détail avec SEO et contenu.

### Étape 1 : Ajouter les colonnes à la table

Utilisez la commande Artisan :

```bash
php artisan page-content-manager:add-page-detail dance_styles --after=name
```

Cela créera une migration qui ajoute :
- `seo_title` (string, nullable)
- `seo_description` (text, nullable)
- `content` (json, nullable)

Exécutez la migration :

```bash
php artisan migrate
```

### Étape 2 : Mettre à jour le modèle

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Xavcha\PageContentManager\Models\Concerns\HasPageDetail;

class DanceStyle extends Model
{
    use HasFactory, HasPageDetail;

    protected $fillable = [
        'name',
        'seo_title',
        'seo_description',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }
}
```

Le trait `HasPageDetail` :
- Utilise automatiquement `HasContentBlocks` pour normaliser le contenu
- Ajoute les méthodes `getSections()` et `getMetadata()`

### Étape 3 : Mettre à jour la ressource Filament

```php
<?php

namespace App\Filament\Resources;

use App\Models\DanceStyle;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Xavcha\PageContentManager\Filament\Forms\Components\PageDetailTabs;

class DanceStyleResource extends Resource
{
    protected static ?string $model = DanceStyle::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Tabs::make('tabs')
                    ->tabs([
                        Components\Tabs\Tab::make('general')
                            ->label('Général')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom de la danse')
                                    ->required()
                                    ->maxLength(255),
                                
                                // Vos autres champs ici
                            ]),
                        ...PageDetailTabs::tabs(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
```

### Étape 4 : Utiliser les données dans votre frontend

Si vous exposez les données via API, vous pouvez utiliser le même système de transformers :

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Xavcha\PageContentManager\Services\Blocks\SectionTransformerService;

class DanceStyleResource extends JsonResource
{
    public function toArray($request): array
    {
        $transformerService = app(SectionTransformerService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'sections' => $transformerService->transform($this->getSections()),
            'metadata' => $this->getMetadata(),
        ];
    }
}
```

## Personnalisation

### Utiliser PageDetailTabs

**Méthode recommandée** : Utiliser `PageDetailTabs::tabs()` :

```php
use Xavcha\PageContentManager\Filament\Forms\Components\PageDetailTabs;

Components\Tabs::make('tabs')
    ->tabs([
        Components\Tabs\Tab::make('general')
            ->label('Général')
            ->schema([...]),
        ...PageDetailTabs::tabs(),
    ]),
```

**Alternative** : Utiliser les onglets individuellement :

```php
use Xavcha\PageContentManager\Filament\Forms\Components\SeoTab;
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;

Components\Tabs::make('tabs')
    ->tabs([
        Components\Tabs\Tab::make('general')
            ->label('Général')
            ->schema([...]),
        SeoTab::make(),
        ContentTab::make(),
    ]),
```

### Utiliser uniquement l'onglet SEO

Si vous ne voulez que l'onglet SEO :

```php
use Xavcha\PageContentManager\Filament\Forms\Components\SeoTab;

Components\Tabs::make('tabs')
    ->tabs([
        Components\Tabs\Tab::make('general')
            ->label('Général')
            ->schema([...]),
        SeoTab::make(),
    ]),
```

### Utiliser uniquement l'onglet Content

```php
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;

Components\Tabs\Tab::make('content')
    ->schema([
        ...ContentTab::make()->schema->getComponents(),
    ]),
```

## Notes importantes

- Le trait `HasPageDetail` normalise automatiquement le contenu lors de la sauvegarde
- Les blocs utilisés sont ceux configurés dans `page-content-manager.blocks`
- Vous pouvez créer des transformers personnalisés pour vos propres blocs
- Le système est compatible avec tous les types de ressources Filament

