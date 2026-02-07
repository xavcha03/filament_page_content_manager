<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Menu\Stores;

use RuntimeException;
use Xavcha\PageContentManager\Menu\Contracts\MenuLinksStore;

class NullMenuLinksStore implements MenuLinksStore
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLinks(): array
    {
        throw new RuntimeException(
            'Menu MCP tools are enabled but no menu links store is configured. '
            . 'Set page-content-manager.menu.links_store to a class implementing '
            . MenuLinksStore::class . '.'
        );
    }

    /**
     * @param array<int, array<string, mixed>> $links
     */
    public function saveLinks(array $links): void
    {
        throw new RuntimeException(
            'Menu MCP tools are enabled but no menu links store is configured. '
            . 'Set page-content-manager.menu.links_store to a class implementing '
            . MenuLinksStore::class . '.'
        );
    }
}

