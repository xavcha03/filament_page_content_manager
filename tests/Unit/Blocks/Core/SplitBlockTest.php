<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\SplitBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class SplitBlockTest extends TestCase
{
    public function test_get_type_returns_split(): void
    {
        $this->assertEquals('split', SplitBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = SplitBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_make_has_correct_schema(): void
    {
        $block = SplitBlock::make();

        $this->assertEquals('split', $block->getName());
        $this->assertEquals('Texte + Image (Split)', $block->getLabel());
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'Test Title',
            'texte' => 'Test Text',
            'variant' => 'left',
            'background' => 'light',
        ];

        $result = SplitBlock::transform($data);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('split', $result['type']);
        $this->assertEquals('Test Title', $result['titre']);
        $this->assertEquals('Test Text', $result['texte']);
        $this->assertEquals('left', $result['variant']);
        $this->assertEquals('light', $result['background']);
    }

    public function test_transform_handles_feature_variant(): void
    {
        $data = [
            'titre' => 'Feature Title',
            'texte' => 'Feature Text',
            'variant' => 'feature',
        ];

        $result = SplitBlock::transform($data);

        $this->assertEquals('feature', $result['variant']);
        $this->assertEquals('light', $result['background']);
    }

    public function test_transform_handles_missing_fields(): void
    {
        $data = [];

        $result = SplitBlock::transform($data);

        $this->assertEquals('split', $result['type']);
        $this->assertEquals('', $result['titre']);
        $this->assertEquals('', $result['texte']);
        $this->assertEquals('left', $result['variant']);
        $this->assertEquals('light', $result['background']);
    }

    public function test_transform_includes_button_if_provided(): void
    {
        $data = [
            'titre' => 'Test',
            'texte' => 'Test',
            'bouton' => [
                'texte' => 'Click me',
                'lien' => '/test',
            ],
        ];

        $result = SplitBlock::transform($data);

        $this->assertArrayHasKey('bouton', $result);
        $this->assertEquals('Click me', $result['bouton']['texte']);
        $this->assertEquals('/test', $result['bouton']['lien']);
    }
}

