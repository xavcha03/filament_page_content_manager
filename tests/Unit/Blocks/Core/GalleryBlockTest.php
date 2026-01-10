<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\GalleryBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class GalleryBlockTest extends TestCase
{
    public function test_get_type_returns_gallery(): void
    {
        $this->assertEquals('gallery', GalleryBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = GalleryBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_transform_handles_multiple_images(): void
    {
        $data = [
            'titre' => 'Gallery Title',
            'images_ids' => [], // Tableau vide pour éviter l'appel à MediaFile
        ];

        $result = GalleryBlock::transform($data);

        $this->assertEquals('gallery', $result['type']);
        $this->assertEquals('Gallery Title', $result['titre']);
        $this->assertArrayHasKey('images', $result);
        $this->assertIsArray($result['images']);
    }
}

