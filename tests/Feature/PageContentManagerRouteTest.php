<?php

namespace Xavcha\PageContentManager\Tests\Feature;

use Xavcha\PageContentManager\Tests\TestCase;

class PageContentManagerRouteTest extends TestCase
{
    public function test_route_renders_page(): void
    {
        $this->get('/page-content-manager')
            ->assertOk()
            ->assertSee('Page Content Manager');
    }
}
