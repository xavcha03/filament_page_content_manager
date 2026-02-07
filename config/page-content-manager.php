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
    | MCP Server
    |--------------------------------------------------------------------------
    |
    | Configuration du serveur MCP (Model Context Protocol) pour permettre
    | aux agents IA de créer et gérer des pages.
    |
    */

    'mcp' => [
        'enabled' => env('PAGE_CONTENT_MANAGER_MCP_ENABLED', true),
        'route' => env('PAGE_CONTENT_MANAGER_MCP_ROUTE', 'mcp/pages'),
        'auto_register' => env('PAGE_CONTENT_MANAGER_MCP_AUTO_REGISTER', true),
        'middleware' => [],
        'token' => env('PAGE_CONTENT_MANAGER_MCP_TOKEN'),
        'token_header' => env('PAGE_CONTENT_MANAGER_MCP_TOKEN_HEADER', 'X-MCP-Token'),
        'require_token' => env('PAGE_CONTENT_MANAGER_MCP_REQUIRE_TOKEN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enregistrement de la ressource Filament
    |--------------------------------------------------------------------------
    |
    | IMPORTANT: L'enregistrement automatique peut ne pas fonctionner correctement
    | car les routes ne sont pas créées à temps. Il est FORTEMENT RECOMMANDÉ
    | d'enregistrer manuellement la ressource dans votre PanelProvider.
    |
    | Pour enregistrer manuellement, ajoutez dans votre PanelProvider :
    |
    | use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;
    |
    | public function panel(Panel $panel): Panel
    | {
    |     return $panel
    |         ->resources([
    |             PageResource::class,
    |             // ... autres ressources
    |         ]);
    | }
    |
    | Si vous souhaitez quand même essayer l'enregistrement automatique
    | (non recommandé), définissez cette option à true.
    |
    */

    'register_filament_resource' => false,

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

    /*
    |--------------------------------------------------------------------------
    | Blocs désactivés
    |--------------------------------------------------------------------------
    |
    | Liste des types de blocs à désactiver. Les blocs listés ici ne seront
    | pas disponibles dans le Builder Filament.
    |
    | Exemple: ['faq', 'contact_form']
    |
    */

    'disabled_blocks' => [],

    /*
    |--------------------------------------------------------------------------
    | Cache des blocs
    |--------------------------------------------------------------------------
    |
    | Configuration du cache pour améliorer les performances de découverte
    | des blocs. Le cache est automatiquement désactivé en environnement local
    | pour permettre la détection immédiate des nouveaux blocs.
    |
    */

    'cache' => [
        'enabled' => env('PAGE_CONTENT_MANAGER_CACHE_ENABLED', true),
        'key' => 'page-content-manager.blocks.registry',
        'ttl' => env('PAGE_CONTENT_MANAGER_CACHE_TTL', 3600), // 1 heure par défaut
    ],

    /*
    |--------------------------------------------------------------------------
    | API - Filtrage des blocs manquants
    |--------------------------------------------------------------------------
    |
    | Lorsqu'un bloc a été supprimé mais qu'il est encore référencé dans le
    | contenu d'une page, cette option contrôle le comportement de l'API :
    |
    | - true (défaut) : Les sections avec des blocs inexistants sont filtrées
    |   et ne sont pas retournées par l'API. C'est le comportement recommandé.
    |
    | - false : Les sections avec des blocs inexistants sont retournées avec
    |   leurs données brutes (mode rétrocompatibilité).
    |
    */

    'api' => [
        'filter_missing_blocks' => env('PAGE_CONTENT_MANAGER_FILTER_MISSING_BLOCKS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation des blocs au démarrage
    |--------------------------------------------------------------------------
    |
    | Active la validation de tous les blocs au démarrage de l'application.
    | Cette validation vérifie que tous les blocs respectent BlockInterface
    | et ont toutes les méthodes requises.
    |
    | - false (défaut) : Désactivée pour ne pas impacter les performances
    | - true : Active la validation (recommandé en développement)
    |
    | Si activée, les erreurs sont loggées. Pour lancer une exception en cas
    | d'erreur, définissez 'validate_blocks_on_boot_throw' à true.
    |
    */

    'validate_blocks_on_boot' => env('PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT', false),
    'validate_blocks_on_boot_throw' => env('PAGE_CONTENT_MANAGER_VALIDATE_BLOCKS_ON_BOOT_THROW', false),

    /*
    |--------------------------------------------------------------------------
    | Groupes de blocs
    |--------------------------------------------------------------------------
    |
    | Définit les groupes de blocs avec leur ordre d'affichage pour chaque
    | contexte d'utilisation (Pages, Articles, etc.).
    |
    | Chaque groupe contient une liste de classes de blocs dans l'ordre
    | souhaité. Les blocs apparaîtront dans le Builder Filament dans cet ordre.
    |
    | Exemple :
    | 'block_groups' => [
    |     'pages' => [
    |         'blocks' => [
    |             \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
    |             \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
    |             // ... autres blocs dans l'ordre souhaité
    |         ],
    |     ],
    |     'articles' => [
    |         'blocks' => [
    |             \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
    |             \Xavcha\PageContentManager\Blocks\Core\ImageBlock::class,
    |         ],
    |     ],
    | ],
    |
    | Si aucun groupe n'est spécifié ou si le groupe n'existe pas, tous les
    | blocs disponibles seront affichés dans l'ordre de découverte.
    |
    */

    'block_groups' => [
        'pages' => [
            'blocks' => [
                \Xavcha\PageContentManager\Blocks\Core\HeroBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\TextBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\ImageBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\SplitBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\FeaturesBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\ServicesBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\CTABlock::class,
                \Xavcha\PageContentManager\Blocks\Core\FAQBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\ContactFormBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\TestimonialsBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\GalleryBlock::class,
                \Xavcha\PageContentManager\Blocks\Core\LogoCloudBlock::class,
            ],
        ],
    ],
];
