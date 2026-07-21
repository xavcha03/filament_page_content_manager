<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Tests\Unit\Blocks;

use Illuminate\Support\Facades\File;
use Xavcha\PageContentManager\Blocks\BlockPreviewResolver;
use Xavcha\PageContentManager\Tests\TestCase;

class BlockPreviewResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        $dir = resource_path('images/block-previews');
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }

        $publicDir = public_path('images/block-previews');
        if (is_dir($publicDir)) {
            File::deleteDirectory($publicDir);
        }

        parent::tearDown();
    }

    public function test_exists_returns_false_when_no_preview_file(): void
    {
        $this->assertFalse(BlockPreviewResolver::exists('hero_missing_' . uniqid()));
        $this->assertNull(BlockPreviewResolver::url('hero_missing_' . uniqid()));
    }

    public function test_resolves_package_or_app_resource_preview(): void
    {
        $type = 'test_preview_' . substr(uniqid(), -6);
        $dir = resource_path('images/block-previews');
        File::ensureDirectoryExists($dir);
        File::put("{$dir}/{$type}.webp", 'RIFF');

        $this->assertTrue(BlockPreviewResolver::exists($type));
        $this->assertSame(
            resource_path("images/block-previews/{$type}.webp"),
            BlockPreviewResolver::resolveFilePath($type)
        );

        $url = BlockPreviewResolver::url($type);
        $this->assertIsString($url);
        $this->assertStringContainsString("_page-content-manager/block-previews/{$type}.webp", $url);
    }

    public function test_public_preview_takes_priority(): void
    {
        $type = 'test_public_' . substr(uniqid(), -6);

        File::ensureDirectoryExists(resource_path('images/block-previews'));
        File::put(resource_path("images/block-previews/{$type}.webp"), 'resource');

        File::ensureDirectoryExists(public_path('images/block-previews'));
        File::put(public_path("images/block-previews/{$type}.webp"), 'public');

        $this->assertSame(
            public_path("images/block-previews/{$type}.webp"),
            BlockPreviewResolver::resolveFilePath($type)
        );

        $url = BlockPreviewResolver::url($type);
        $this->assertIsString($url);
        $this->assertStringContainsString("images/block-previews/{$type}.webp", $url);
    }

    public function test_custom_get_preview_image_url_wins(): void
    {
        $blockClass = new class {
            public static function getPreviewImageUrl(): ?string
            {
                return 'https://example.test/custom.webp';
            }
        };

        $url = BlockPreviewResolver::url('anything', $blockClass::class);
        $this->assertSame('https://example.test/custom.webp', $url);
    }

    public function test_rejects_invalid_type(): void
    {
        $this->assertNull(BlockPreviewResolver::resolveFilePath('../evil'));
        $this->assertNull(BlockPreviewResolver::resolveFilePath('Hero'));
    }
}
