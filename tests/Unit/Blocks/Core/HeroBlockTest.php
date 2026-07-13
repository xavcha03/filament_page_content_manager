<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\Core\HeroBlock;
use Xavcha\PageContentManager\Mcp\Helpers\BlockDataValidator;
use Xavcha\PageContentManager\Mcp\Helpers\BlockInfoExtractor;
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

    public function test_get_mcp_fields_includes_media_fields(): void
    {
        $fields = HeroBlock::getMcpFields();
        $fieldNames = array_column($fields, 'name');

        $this->assertContains('image_fond_id', $fieldNames);
        $this->assertContains('image_fond_alt', $fieldNames);
        $this->assertContains('images_ids', $fieldNames);
        $this->assertContains('image_fond', $fieldNames);
    }

    public function test_get_mcp_example_includes_media_fields(): void
    {
        $example = HeroBlock::getMcpExample();

        $this->assertArrayHasKey('image_fond_id', $example);
        $this->assertArrayHasKey('image_fond_alt', $example);
    }

    public function test_block_info_extractor_exposes_media_fields_for_hero(): void
    {
        $info = BlockInfoExtractor::extract('hero', HeroBlock::class);
        $fieldNames = array_column($info['fields'], 'name');

        $this->assertContains('image_fond_id', $fieldNames);
        $this->assertContains('image_fond_alt', $fieldNames);
        $this->assertContains('images_ids', $fieldNames);
    }

    public function test_validator_accepts_hero_with_image_fields(): void
    {
        $registry = app(BlockRegistry::class);
        $registry->register('hero', HeroBlock::class);

        $validator = new BlockDataValidator($registry);
        $result = $validator->validateBlockData('hero', [
            'titre' => 'Titre hero',
            'description' => 'Description hero',
            'variant' => 'hero',
            'image_fond_id' => 123,
            'image_fond_alt' => 'Image d\'accueil',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertNull($result['error']);
    }

    public function test_validator_accepts_partial_update_merge_with_existing_image(): void
    {
        $registry = app(BlockRegistry::class);
        $registry->register('hero', HeroBlock::class);

        $existingData = [
            'titre' => 'Titre hero',
            'description' => 'Ancienne description',
            'variant' => 'hero',
            'image_fond_id' => 123,
            'image_fond_alt' => 'Image d\'accueil',
        ];

        $patchedData = array_replace_recursive($existingData, [
            'description' => 'Nouvelle description courte',
        ]);

        $validator = new BlockDataValidator($registry);
        $result = $validator->validateBlockData('hero', $patchedData);

        $this->assertTrue($result['ok']);
        $this->assertNull($result['error']);
    }

    public function test_validator_accepts_legacy_image_fond_field(): void
    {
        $registry = app(BlockRegistry::class);
        $registry->register('hero', HeroBlock::class);

        $validator = new BlockDataValidator($registry);
        $result = $validator->validateBlockData('hero', [
            'titre' => 'Titre hero',
            'description' => 'Description hero',
            'variant' => 'hero',
            'image_fond' => '/storage/test.jpg',
            'image_fond_alt' => 'Alt text',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertNull($result['error']);
    }
}

