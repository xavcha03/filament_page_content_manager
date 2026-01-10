<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\TextBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class TextBlockTest extends TestCase
{
    public function test_get_type_returns_text(): void
    {
        $this->assertEquals('text', TextBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = TextBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'Test Title',
            'content' => 'Test Content',
        ];

        $result = TextBlock::transform($data);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('text', $result['type']);
        $this->assertEquals('Test Title', $result['titre']);
        $this->assertEquals('Test Content', $result['content']);
    }

    public function test_transform_handles_missing_fields(): void
    {
        $data = [];

        $result = TextBlock::transform($data);

        $this->assertEquals('text', $result['type']);
        $this->assertEquals('', $result['titre']);
        $this->assertEquals('', $result['content']);
    }
}


