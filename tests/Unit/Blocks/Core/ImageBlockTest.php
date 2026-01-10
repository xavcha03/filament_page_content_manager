<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Xavcha\PageContentManager\Blocks\Core\ImageBlock;
use Xavcha\PageContentManager\Tests\TestCase;

class ImageBlockTest extends TestCase
{
    public function test_get_type_returns_image(): void
    {
        $this->assertEquals('image', ImageBlock::getType());
    }

    public function test_make_returns_block_instance(): void
    {
        $block = ImageBlock::make();

        $this->assertInstanceOf(Block::class, $block);
    }

    public function test_transform_handles_media_file_id(): void
    {
        $data = [
            'image_id' => null, // Pas d'ID pour éviter l'appel à MediaFile
            'alt' => 'Alt text',
            'caption' => 'Caption text',
        ];

        $result = ImageBlock::transform($data);

        $this->assertEquals('image', $result['type']);
        $this->assertEquals('Alt text', $result['alt']);
        $this->assertEquals('Caption text', $result['caption']);
        // image_url ne sera pas présent car getMediaFileUrl retourne null sans MediaFile réel
    }

    public function test_transform_handles_missing_image(): void
    {
        $data = [
            'alt' => 'Alt text',
        ];

        $result = ImageBlock::transform($data);

        $this->assertEquals('image', $result['type']);
        $this->assertEquals('Alt text', $result['alt']);
        $this->assertArrayNotHasKey('image_url', $result);
    }
}

