<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp;

final class Messages
{
    /** Message when no page identifier is provided to a page-scoped tool. */
    public const PAGE_IDENTIFIER_REQUIRED = 'Provide exactly one of: page_id, page_slug (e.g. \'home\' for home page).';
}
