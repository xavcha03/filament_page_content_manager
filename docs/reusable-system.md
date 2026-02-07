# Systeme reutilisable (SEO + Content)

Vous pouvez ajouter les onglets SEO + Content a d'autres ressources Filament.

## 1. Ajouter les colonnes

```bash
php artisan page-content-manager:add-page-detail <table> --after=<column>
```

## 2. Ajouter le trait au modele

```php
use Xavcha\PageContentManager\Models\Concerns\HasPageDetail;

class Article extends Model
{
    use HasPageDetail;

    protected $fillable = [
        'title',
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

## 3. Ajouter les tabs Filament

```php
use Xavcha\PageContentManager\Filament\Forms\Components\PageDetailTabs;

Components\Tabs::make('tabs')
    ->tabs([
        // ... vos tabs
        ...PageDetailTabs::tabs(),
    ]);
```

## Notes

- Utilisez `ContentTab::make('group')` si vous voulez limiter les blocs.
- Les blocs sont auto-decouverts.
