<?php

namespace Xavcha\PageContentManager\Tests;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Xavcha\PageContentManager\PageContentManagerServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            FilamentServiceProvider::class,
            PageContentManagerServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Configuration de la base de données pour les tests
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configuration Filament
        $app['config']->set('filament', [
            'default' => 'admin',
            'panels' => [
                'admin' => [
                    'id' => 'admin',
                    'path' => 'admin',
                ],
            ],
        ]);
    }
}
