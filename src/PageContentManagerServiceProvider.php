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
            ]);
        }

        // Enregistrer automatiquement la ressource Filament pour tous les panels
        if (config('page-content-manager.register_filament_resource', true)) {
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

