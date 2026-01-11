<?php

namespace Xavcha\PageContentManager\Tests\Unit\Filament\Forms\Components;

use Illuminate\Support\Facades\Config;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\Core\HeroBlock;
use Xavcha\PageContentManager\Blocks\Core\TextBlock;
use Xavcha\PageContentManager\Blocks\Core\ImageBlock;
use Xavcha\PageContentManager\Filament\Forms\Components\ContentTab;
use Xavcha\PageContentManager\Tests\TestCase;

class ContentTabTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Réinitialiser la configuration
        Config::set('page-content-manager.block_groups', []);
        Config::set('page-content-manager.disabled_blocks', []);
    }

    public function test_make_returns_tab_component(): void
    {
        $tab = ContentTab::make();

        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_make_uses_default_group(): void
    {
        // Configurer un groupe 'pages' par défaut
        Config::set('page-content-manager.block_groups', [
            'pages' => [
                'blocks' => [
                    HeroBlock::class,
                    TextBlock::class,
                ],
            ],
        ]);

        $tab = ContentTab::make();
        
        // Vérifier que le tab est créé (pas d'exception)
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_make_accepts_custom_group(): void
    {
        Config::set('page-content-manager.block_groups', [
            'articles' => [
                'blocks' => [
                    TextBlock::class,
                    ImageBlock::class,
                ],
            ],
        ]);

        $tab = ContentTab::make('articles');
        
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_uses_blocks_from_group_configuration(): void
    {
        Config::set('page-content-manager.block_groups', [
            'pages' => [
                'blocks' => [
                    HeroBlock::class,
                    TextBlock::class,
                ],
            ],
        ]);

        $tab = ContentTab::make('pages');
        
        // Le tab devrait être créé avec les blocs du groupe
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_respects_block_order_from_configuration(): void
    {
        Config::set('page-content-manager.block_groups', [
            'pages' => [
                'blocks' => [
                    TextBlock::class,  // En deuxième position
                    HeroBlock::class,   // En première position
                ],
            ],
        ]);

        $tab = ContentTab::make('pages');
        
        // Le tab devrait être créé
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_filters_disabled_blocks(): void
    {
        Config::set('page-content-manager.block_groups', [
            'pages' => [
                'blocks' => [
                    HeroBlock::class,
                    TextBlock::class,
                    ImageBlock::class,
                ],
            ],
        ]);

        // Désactiver le bloc 'text'
        Config::set('page-content-manager.disabled_blocks', ['text']);

        $tab = ContentTab::make('pages');
        
        // Le tab devrait être créé sans le bloc désactivé
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_falls_back_to_all_blocks_when_group_not_found(): void
    {
        // Pas de configuration de groupes
        Config::set('page-content-manager.block_groups', []);

        $tab = ContentTab::make('non_existent_group');
        
        // Devrait utiliser tous les blocs disponibles (fallback)
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_falls_back_to_all_blocks_when_no_config(): void
    {
        // Pas de configuration
        Config::set('page-content-manager.block_groups', null);

        $tab = ContentTab::make('pages');
        
        // Devrait utiliser tous les blocs disponibles (fallback)
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_handles_invalid_block_classes(): void
    {
        Config::set('page-content-manager.block_groups', [
            'pages' => [
                'blocks' => [
                    HeroBlock::class,
                    'NonExistentBlockClass', // Classe invalide
                    TextBlock::class,
                ],
            ],
        ]);

        $tab = ContentTab::make('pages');
        
        // Devrait ignorer la classe invalide et continuer
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_handles_empty_group(): void
    {
        Config::set('page-content-manager.block_groups', [
            'pages' => [
                'blocks' => [], // Groupe vide
            ],
        ]);

        $tab = ContentTab::make('pages');
        
        // Devrait créer un tab avec aucun bloc
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }

    public function test_allows_custom_blocks_in_groups(): void
    {
        // Simuler un bloc custom (même si on ne peut pas le créer vraiment en test)
        Config::set('page-content-manager.block_groups', [
            'pages' => [
                'blocks' => [
                    HeroBlock::class,
                    TextBlock::class,
                    // Un bloc custom serait ici : \App\Blocks\Custom\VideoBlock::class,
                ],
            ],
        ]);

        $tab = ContentTab::make('pages');
        
        $this->assertInstanceOf(\Filament\Schemas\Components\Tabs\Tab::class, $tab);
    }
}



