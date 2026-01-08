<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Routes API
    |--------------------------------------------------------------------------
    |
    | Configuration des routes API pour exposer les pages.
    |
    */

    'routes' => true,
    'route_prefix' => 'api',
    'route_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Enregistrement de la ressource Filament
    |--------------------------------------------------------------------------
    |
    | La ressource Page est automatiquement découverte par Filament.
    | Si vous souhaitez l'enregistrer manuellement, ajoutez-la dans
    | votre PanelProvider :
    |
    | use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;
    |
    | $panel->resources([
    |     PageResource::class,
    | ]);
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Modèles
    |--------------------------------------------------------------------------
    |
    | Configuration des modèles utilisés par le package.
    |
    */

    'models' => [
        'page' => \Xavcha\PageContentManager\Models\Page::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocs de contenu
    |--------------------------------------------------------------------------
    |
    | Configuration des blocs disponibles pour les pages.
    | Les blocs sont organisés en deux catégories :
    | - 'core' : Blocs de base inclus par défaut dans le package
    | - 'custom' : Blocs spécifiques au projet (vide par défaut)
    |
    | Pour désactiver un bloc core, retirez-le simplement de la liste.
    | Pour ajouter un bloc custom, créez-le dans votre projet et ajoutez-le ici.
    |
    | Format : 'key' => 'Full\Class\Name::class'
    | ou simplement : 'Full\Class\Name::class' (la clé sera le nom de la classe)
    |
    */

    'blocks' => [
        'core' => [
            'hero' => \Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core\HeroBlock::class,
            'text' => \Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core\TextBlock::class,
            'image' => \Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core\ImageBlock::class,
            'gallery' => \Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core\GalleryBlock::class,
            'cta' => \Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core\CTABlock::class,
            'faq' => \Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core\FAQBlock::class,
            'contact_form' => \Xavcha\PageContentManager\Filament\Forms\Components\Blocks\Core\ContactFormBlock::class,
        ],
        'custom' => [
            // Ajoutez ici vos blocs personnalisés
            // Exemple: 'mon_bloc' => \App\Filament\Forms\Components\Blocks\Custom\MonBloc::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transformers
    |--------------------------------------------------------------------------
    |
    | Configuration pour les transformers de blocs (utilisés pour l'API).
    | Les transformers sont auto-découverts dans :
    | - Le package : src/Services/Blocks/Transformers/Core/
    | - L'application : app/Services/Blocks/Transformers/Core/ et Custom/
    |
    | Vous pouvez créer vos propres transformers dans votre application
    | en implémentant BlockTransformerInterface.
    |
    */

    'transformers' => [
        'custom_namespace' => 'App\\Services\\Blocks\\Transformers\\Custom',
        'custom_path' => app_path('Services/Blocks/Transformers/Custom'),
    ],
];
