<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\HeroBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class HeroBlockTest extends TestCase
{
    public function test_get_type_returns_hero(): void
    {
        $this->assertEquals('hero', HeroBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = HeroBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_make_has_correct_schema(): void
    {
        $block = HeroBlock::make();

        $this->assertEquals('hero', $block->getName());
        $this->assertEquals('Section Hero', $block->getLabel());
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'Test Title',
            'description' => 'Test Description',
            'variant' => 'hero',
        ];

        $result = HeroBlock::transform($data);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('hero', $result['type']);
        $this->assertEquals('Test Title', $result['titre']);
        $this->assertEquals('Test Description', $result['description']);
        $this->assertEquals('hero', $result['variant']);
    }

    public function test_transform_handles_hero_variant(): void
    {
        $data = [
            'titre' => 'Hero Title',
            'description' => 'Hero Description',
            'variant' => 'hero',
            'image_fond_id' => null, // Pas d'ID pour éviter l'appel à MediaFile
            'image_fond_alt' => 'Alt text',
        ];

        $result = HeroBlock::transform($data);

        $this->assertEquals('hero', $result['variant']);
        // image_fond ne sera pas dans le résultat car getMediaFileUrl retourne null sans MediaFile réel
        // mais la structure devrait être correcte
        $this->assertArrayHasKey('variant', $result);
    }

    public function test_transform_handles_projects_variant(): void
    {
        $data = [
            'titre' => 'Projects Title',
            'description' => 'Projects Description',
            'variant' => 'projects',
            'images_ids' => [], // Tableau vide pour éviter l'appel à MediaFile
        ];

        $result = HeroBlock::transform($data);

        $this->assertEquals('projects', $result['variant']);
        $this->assertArrayHasKey('images', $result);
        $this->assertIsArray($result['images']);
    }

    public function test_transform_handles_missing_fields(): void
    {
        $data = [];

        $result = HeroBlock::transform($data);

        $this->assertEquals('hero', $result['type']);
        $this->assertEquals('', $result['titre']);
        $this->assertEquals('', $result['description']);
        $this->assertEquals('hero', $result['variant']);
    }

    public function test_transform_includes_button_if_provided(): void
    {
        $data = [
            'titre' => 'Test',
            'description' => 'Test',
            'bouton_principal' => [
                'texte' => 'Click me',
                'lien' => '/test',
            ],
        ];

        $result = HeroBlock::transform($data);

        $this->assertArrayHasKey('bouton_principal', $result);
        $this->assertEquals('Click me', $result['bouton_principal']['texte']);
        $this->assertEquals('/test', $result['bouton_principal']['lien']);
    }

    public function test_transform_handles_legacy_image_fond(): void
    {
        $data = [
            'titre' => 'Test',
            'description' => 'Test',
            'variant' => 'hero',
            'image_fond' => '/storage/test.jpg',
            'image_fond_alt' => 'Alt text',
        ];

        $result = HeroBlock::transform($data);

        $this->assertArrayHasKey('image_fond', $result);
        $this->assertArrayHasKey('image_fond_alt', $result);
    }
}

