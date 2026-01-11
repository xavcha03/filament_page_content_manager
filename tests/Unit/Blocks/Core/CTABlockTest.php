<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\CTABlock;
use Xavcha\PageContentManager\Tests\TestCase;

class CTABlockTest extends TestCase
{
    public function test_get_type_returns_cta(): void
    {
        $this->assertEquals('cta', CTABlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = CTABlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'CTA Title',
            'description' => 'CTA Description',
            'cta_text' => 'Click me',
            'cta_link' => '/test',
        ];

        $result = CTABlock::transform($data);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('cta', $result['type']);
        $this->assertEquals('CTA Title', $result['titre']);
        $this->assertEquals('CTA Description', $result['description']);
        $this->assertEquals('Click me', $result['cta_text']);
        $this->assertEquals('/test', $result['cta_link']);
    }

    public function test_transform_handles_missing_fields(): void
    {
        $data = [];

        $result = CTABlock::transform($data);

        $this->assertEquals('cta', $result['type']);
        $this->assertEquals('', $result['titre']);
        $this->assertEquals('', $result['description']);
        $this->assertEquals('', $result['cta_text']);
        $this->assertEquals('', $result['cta_link']);
    }
}




