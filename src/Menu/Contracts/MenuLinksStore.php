<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Menu\Contracts;

interface MenuLinksStore
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLinks(): array;

    /**
     * @param array<int, array<string, mixed>> $links
     */
    public function saveLinks(array $links): void;
}

