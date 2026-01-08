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
    | Les blocs sont maintenant auto-découverts dans :
    | - Package : src/Blocks/Core/
    | - Application : app/Blocks/Custom/
    |
    | Vous n'avez plus besoin de les configurer ici, sauf pour :
    | 1. Désactiver un bloc core (retirez-le de la liste)
    | 2. Utiliser l'ancien système (rétrocompatibilité)
    |
    | NOUVEAU SYSTÈME (recommandé) :
    | Créez vos blocs dans app/Blocks/Custom/ en implémentant BlockInterface.
    | Chaque bloc contient à la fois le formulaire Filament ET la méthode transform().
    |
    | ANCIEN SYSTÈME (rétrocompatibilité) :
    | Utilisez la configuration ci-dessous pour pointer vers les anciens blocs.
    |
    */

    'blocks' => [
        'core' => [
            // Les blocs core sont auto-découverts depuis src/Blocks/Core/
            // Vous pouvez les désactiver en les retirant de cette liste
            // ou en les commentant
        ],
        'custom' => [
            // Les blocs custom sont auto-découverts depuis app/Blocks/Custom/
            // Vous pouvez aussi les enregistrer manuellement ici si besoin
        ],
    ],
];
