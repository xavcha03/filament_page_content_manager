<?php

use Illuminate\Support\Facades\Route;
use Xavcha\PageContentManager\Http\Controllers\Api\PageController;

Route::group([
    'prefix' => config('page-content-manager.route_prefix', 'api'),
    'middleware' => config('page-content-manager.route_middleware', ['api']),
], function () {
    Route::get('/pages', [PageController::class, 'index'])->name('page-content-manager.pages.index');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('page-content-manager.pages.show');
});
