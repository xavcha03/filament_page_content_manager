<?php

namespace Xavcha\PageContentManager;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\BlockValidator;
use Xavcha\PageContentManager\Menu\Contracts\MenuLinksStore;
use Xavcha\PageContentManager\Menu\MenuLinksService;
use Xavcha\PageContentManager\Menu\Stores\NullMenuLinksStore;
use Xavcha\PageContentManager\Mcp\PageMcpServer;

class PageContentManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/page-content-manager.php', 'page-content-manager');

        // Enregistrer BlockRegistry comme singleton pour la Facade
        $this->app->singleton(BlockRegistry::class, function ($app) {
            return new BlockRegistry();
        });

        $this->app->bind(MenuLinksStore::class, function ($app): MenuLinksStore {
            $storeClass = config('page-content-manager.menu.links_store');

            if (is_string($storeClass) && $storeClass !== '' && class_exists($storeClass)) {
                $instance = $app->make($storeClass);

                if ($instance instanceof MenuLinksStore) {
                    return $instance;
                }
            }

            return $app->make(NullMenuLinksStore::class);
        });

        $this->app->bind(MenuLinksService::class, function ($app): MenuLinksService {
            return new MenuLinksService($app->make(MenuLinksStore::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Enregistrer les routes API si activées
        if (config('page-content-manager.routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        // Enregistrer le serveur MCP si activé et si l'enregistrement automatique est activé
        if (
            config('page-content-manager.mcp.enabled', true) &&
            config('page-content-manager.mcp.auto_register', true) &&
            class_exists(\Laravel\Mcp\Facades\Mcp::class)
        ) {
            $mcpRoute = config('page-content-manager.mcp.route', 'mcp/pages');
            $route = Mcp::web($mcpRoute, PageMcpServer::class);

            $additionalMiddleware = (array) config('page-content-manager.mcp.middleware', []);
            $token = (string) config('page-content-manager.mcp.token', '');
            $requireToken = (bool) config('page-content-manager.mcp.require_token', false);

            if ($token !== '' || $requireToken) {
                $additionalMiddleware[] = \Xavcha\PageContentManager\Mcp\Middleware\EnsureMcpToken::class;
            }

            if (! empty($additionalMiddleware)) {
                $route->middleware($additionalMiddleware);
            }
        }

        // Publier la configuration
        $this->publishes([
            __DIR__ . '/../config/page-content-manager.php' => config_path('page-content-manager.php'),
        ], 'page-content-manager-config');

        // Enregistrer les commandes Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Xavcha\PageContentManager\Console\Commands\AddPageDetailColumnsCommand::class,
                \Xavcha\PageContentManager\Console\Commands\ClearBlocksCacheCommand::class,
                \Xavcha\PageContentManager\Console\Commands\MakeBlockCommand::class,
                \Xavcha\PageContentManager\Console\Commands\BlocksCommand::class,
                \Xavcha\PageContentManager\Console\Commands\BlockListCommand::class,
                \Xavcha\PageContentManager\Console\Commands\BlockInspectCommand::class,
                \Xavcha\PageContentManager\Console\Commands\BlockDisableCommand::class,
                \Xavcha\PageContentManager\Console\Commands\BlockEnableCommand::class,
                \Xavcha\PageContentManager\Console\Commands\BlocksStatsCommand::class,
                \Xavcha\PageContentManager\Console\Commands\BlocksValidateCommand::class,
            ]);
        }

        // Validation optionnelle des blocs au démarrage
        // Désactivée par défaut pour ne pas impacter les performances en production
        if (config('page-content-manager.validate_blocks_on_boot', false)) {
            $this->validateBlocks();
        }

        // Enregistrement de la ressource Filament
        // IMPORTANT: L'enregistrement automatique via Filament::serving() peut ne pas fonctionner
        // correctement car les routes ne sont pas créées à temps. Il est FORTEMENT RECOMMANDÉ
        // d'enregistrer manuellement la ressource dans votre PanelProvider.
        //
        // Pour enregistrer manuellement, ajoutez dans votre PanelProvider :
        // use Xavcha\PageContentManager\Filament\Resources\Pages\PageResource;
        // ->resources([PageResource::class])
        //
        // Si vous souhaitez quand même essayer l'enregistrement automatique (non recommandé),
        // définissez 'register_filament_resource' => true dans la config.
        if (config('page-content-manager.register_filament_resource', false)) {
            // Essayer d'enregistrer via Filament::serving() (peut ne pas fonctionner)
            Filament::serving(function () {
                foreach (Filament::getPanels() as $panel) {
                    $panel->resources([
                        \Xavcha\PageContentManager\Filament\Resources\Pages\PageResource::class,
                    ]);
                }
            });
        }
    }

    /**
     * Valide tous les blocs enregistrés.
     *
     * @return void
     * @throws \RuntimeException Si un bloc a des erreurs et que la configuration le demande
     */
    protected function validateBlocks(): void
    {
        $registry = $this->app->make(BlockRegistry::class);
        $throwOnError = config('page-content-manager.validate_blocks_on_boot_throw', false);

        BlockValidator::validateAll($registry, $throwOnError);
    }
}
