<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Xavcha\PageContentManager\Blocks\BlockPreviewResolver;

Route::get('_page-content-manager/block-previews/{type}.webp', function (string $type) {
    $path = BlockPreviewResolver::resolveFilePath($type);

    abort_unless(is_string($path) && is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'image/webp',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('type', '[a-z0-9_]+')->name('page-content-manager.block-preview');
