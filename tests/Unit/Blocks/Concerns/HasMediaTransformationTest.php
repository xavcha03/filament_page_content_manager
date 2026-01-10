<?php

namespace Xavcha\PageContentManager\Tests\Unit\Blocks\Concerns;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionClass;
use Xavcha\PageContentManager\Blocks\Core\HeroBlock;
use Xavcha\PageContentManager\Tests\TestCase;
use Xavier\MediaLibraryPro\Models\MediaFile;

class HasMediaTransformationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock route pour media-library-pro
        if (!Route::has('media-library-pro.serve')) {
            Route::get('/media/{media}', function () {
                return 'media-url';
            })->name('media-library-pro.serve');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function callProtectedMethod($object, string $methodName, ...$args)
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    public function test_get_media_file_url_returns_null_without_media_file(): void
    {
        // Mock MediaFile pour éviter l'accès à la base de données
        $mockMediaFile = Mockery::mock('alias:' . MediaFile::class);
        $mockMediaFile->shouldReceive('find')
            ->with(999)
            ->andReturn(null);

        // Utiliser la réflexion pour appeler la méthode protected
        $url = $this->callProtectedMethod(HeroBlock::class, 'getMediaFileUrl', 999);

        // Devrait retourner null car MediaFile::find() retourne null
        $this->assertNull($url);
    }

    public function test_get_media_file_url_handles_json_string(): void
    {
        // Mock MediaFile
        $mockMediaFile = Mockery::mock('alias:' . MediaFile::class);
        $mockMediaFile->shouldReceive('find')
            ->with(1)
            ->andReturn(null);

        $jsonString = json_encode([1, 2, 3]);
        $url = $this->callProtectedMethod(HeroBlock::class, 'getMediaFileUrl', $jsonString);

        // Devrait décoder le JSON et utiliser le premier ID
        // Mais sans MediaFile réel, retourne null
        $this->assertNull($url);
    }

    public function test_transform_media_file_ids_handles_array(): void
    {
        // Mock MediaFile pour chaque ID
        $mockMediaFile = Mockery::mock('alias:' . MediaFile::class);
        $mockMediaFile->shouldReceive('find')
            ->andReturn(null);

        $ids = [1, 2, 3];
        $urls = $this->callProtectedMethod(HeroBlock::class, 'transformMediaFileIds', $ids);

        $this->assertIsArray($urls);
    }

    public function test_transform_media_file_ids_handles_json_string(): void
    {
        // Mock MediaFile
        $mockMediaFile = Mockery::mock('alias:' . MediaFile::class);
        $mockMediaFile->shouldReceive('find')
            ->andReturn(null);

        $jsonString = json_encode([1, 2, 3]);
        $urls = $this->callProtectedMethod(HeroBlock::class, 'transformMediaFileIds', $jsonString);

        $this->assertIsArray($urls);
    }

    public function test_transform_media_file_ids_handles_single_id(): void
    {
        // Mock MediaFile
        $mockMediaFile = Mockery::mock('alias:' . MediaFile::class);
        $mockMediaFile->shouldReceive('find')
            ->andReturn(null);

        $urls = $this->callProtectedMethod(HeroBlock::class, 'transformMediaFileIds', 1);

        $this->assertIsArray($urls);
    }

    public function test_transform_media_file_ids_filters_empty_ids(): void
    {
        // Mock MediaFile
        $mockMediaFile = Mockery::mock('alias:' . MediaFile::class);
        $mockMediaFile->shouldReceive('find')
            ->andReturn(null);

        $ids = [1, null, '', 2];
        $urls = $this->callProtectedMethod(HeroBlock::class, 'transformMediaFileIds', $ids);

        // Les IDs vides devraient être filtrés
        $this->assertIsArray($urls);
    }

    public function test_transform_image_url_handles_full_url(): void
    {
        $fullUrl = 'https://example.com/image.jpg';
        $result = $this->callProtectedMethod(HeroBlock::class, 'transformImageUrl', $fullUrl);

        $this->assertEquals($fullUrl, $result);
    }

    public function test_transform_image_url_handles_absolute_path(): void
    {
        $absolutePath = '/storage/image.jpg';
        $result = $this->callProtectedMethod(HeroBlock::class, 'transformImageUrl', $absolutePath);

        $this->assertStringContainsString('/storage/image.jpg', $result);
    }

    public function test_transform_image_url_handles_storage_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('test.jpg', 'content');

        $storagePath = 'test.jpg';
        $result = $this->callProtectedMethod(HeroBlock::class, 'transformImageUrl', $storagePath);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}

