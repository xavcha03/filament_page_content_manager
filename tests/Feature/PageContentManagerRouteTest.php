<?php

namespace Xavcha\PageContentManager\Tests\Feature;

use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Tests\TestCase;

class PageContentManagerRouteTest extends TestCase
{
    public function test_api_route_returns_pages(): void
    {
        // La page Home est créée par la migration
        $response = $this->getJson('/api/pages');

        $response->assertOk();
        $response->assertJsonStructure([
            'pages' => [
                '*' => ['id', 'title', 'slug', 'type'],
            ],
        ]);
    }
}
