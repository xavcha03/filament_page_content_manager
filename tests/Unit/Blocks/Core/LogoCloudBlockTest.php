<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\LogoCloudBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class LogoCloudBlockTest extends TestCase
{
    public function test_get_type_returns_logo_cloud(): void
    {
        $this->assertEquals('logo_cloud', LogoCloudBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = LogoCloudBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_make_has_correct_schema(): void
    {
        $block = LogoCloudBlock::make();

        $this->assertEquals('logo_cloud', $block->getName());
        $this->assertEquals('Logo Cloud (Clients/Partenaires)', $block->getLabel());
    }

    public function test_transform_returns_correct_structure(): void
    {
        $data = [
            'titre' => 'Test Title',
            'logos' => [
                [
                    'nom' => 'Company 1',
                    'lien' => 'https://example.com',
                ],
            ],
        ];

        $result = LogoCloudBlock::transform($data);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('logo_cloud', $result['type']);
        $this->assertEquals('Test Title', $result['titre']);
        $this->assertIsArray($result['logos']);
        $this->assertCount(1, $result['logos']);
    }

    public function test_transform_handles_missing_fields(): void
    {
        $data = [];

        $result = LogoCloudBlock::transform($data);

        $this->assertEquals('logo_cloud', $result['type']);
        $this->assertEquals('', $result['titre']);
        $this->assertIsArray($result['logos']);
    }

    public function test_transform_handles_multiple_logos(): void
    {
        $data = [
            'logos' => [
                [
                    'nom' => 'Company 1',
                ],
                [
                    'nom' => 'Company 2',
                ],
            ],
        ];

        $result = LogoCloudBlock::transform($data);

        $this->assertCount(2, $result['logos']);
        $this->assertEquals('Company 1', $result['logos'][0]['nom']);
        $this->assertEquals('Company 2', $result['logos'][1]['nom']);
    }
}

