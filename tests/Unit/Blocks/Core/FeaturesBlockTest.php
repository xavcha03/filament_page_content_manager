<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\FeaturesBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class FeaturesBlockTest extends TestCase
{
    public function test_get_type_returns_features(): void
    {
        $this->assertEquals('features', FeaturesBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = FeaturesBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_make_has_correct_schema(): void
    {
        $block = FeaturesBlock::make();

        $this->assertEquals('features', $block->getName());
        $this->assertEquals('Features / Avantages', $block->getLabel());
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'Test Title',
            'description' => 'Test Description',
            'columns' => '3',
            'items' => [
                [
                    'icone' => 'star',
                    'titre' => 'Feature 1',
                    'texte' => 'Description 1',
                ],
            ],
        ];

        $result = FeaturesBlock::transform($data);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('features', $result['type']);
        $this->assertEquals('Test Title', $result['titre']);
        $this->assertEquals('Test Description', $result['description']);
        $this->assertEquals(3, $result['columns']);
        $this->assertIsArray($result['items']);
        $this->assertCount(1, $result['items']);
    }

    public function test_transform_handles_missing_fields(): void
    {
        $data = [];

        $result = FeaturesBlock::transform($data);

        $this->assertEquals('features', $result['type']);
        $this->assertEquals('', $result['titre']);
        $this->assertEquals('', $result['description']);
        $this->assertEquals(3, $result['columns']);
        $this->assertIsArray($result['items']);
    }

    public function test_transform_handles_multiple_items(): void
    {
        $data = [
            'items' => [
                [
                    'icone' => 'star',
                    'titre' => 'Feature 1',
                    'texte' => 'Description 1',
                ],
                [
                    'icone' => 'check',
                    'titre' => 'Feature 2',
                    'texte' => 'Description 2',
                ],
            ],
        ];

        $result = FeaturesBlock::transform($data);

        $this->assertCount(2, $result['items']);
        $this->assertEquals('Feature 1', $result['items'][0]['titre']);
        $this->assertEquals('Feature 2', $result['items'][1]['titre']);
    }
}

