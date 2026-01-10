<?php

namespace Xavcha\PageContentManager;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class PageContentManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/page-content-manager.php', 'page-content-manager');
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
}

