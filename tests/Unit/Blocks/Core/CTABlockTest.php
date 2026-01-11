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

    public function test_transform_handles_different_variants(): void
    {
        // Test variant simple
        $dataSimple = [
            'titre' => 'CTA Title',
            'description' => 'CTA Description',
            'variant' => 'simple',
            'cta_text' => 'Click me',
            'cta_link' => '/test',
        ];

        $resultSimple = CTABlock::transform($dataSimple);
        $this->assertEquals('simple', $resultSimple['variant']);
        $this->assertEquals('CTA Title', $resultSimple['titre']);

        // Test variant hero
        $dataHero = [
            'titre' => 'Hero CTA',
            'variant' => 'hero',
            'cta_text' => 'Click',
            'cta_link' => '/hero',
            'background_image' => '/storage/test.jpg',
            'phone_number' => '123456789',
        ];

        $resultHero = CTABlock::transform($dataHero);
        $this->assertEquals('hero', $resultHero['variant']);
        $this->assertArrayHasKey('background_image', $resultHero);
        $this->assertArrayHasKey('phone_number', $resultHero);

        // Test variant subscription
        $dataSubscription = [
            'titre' => 'Subscription CTA',
            'variant' => 'subscription',
            'cta_text' => 'Subscribe',
            'cta_link' => '/subscribe',
            'secondary_cta_text' => 'Learn more',
        ];

        $resultSubscription = CTABlock::transform($dataSubscription);
        $this->assertEquals('subscription', $resultSubscription['variant']);
        $this->assertArrayHasKey('secondary_cta_text', $resultSubscription);
    }
}




