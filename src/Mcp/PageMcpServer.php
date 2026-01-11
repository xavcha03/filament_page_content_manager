<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp;

use Laravel\Mcp\Server;
use Xavcha\PageContentManager\Mcp\Tools\AddBlocksToPageTool;
use Xavcha\PageContentManager\Mcp\Tools\CreatePageTool;
use Xavcha\PageContentManager\Mcp\Tools\ListBlocksTool;
use Xavcha\PageContentManager\Mcp\Tools\ListPagesTool;
use Xavcha\PageContentManager\Mcp\Tools\UpdatePageTool;

class PageMcpServer extends Server
{
    protected string $name = 'Page Content Manager MCP Server';

    protected string $version = '0.2.4';

    protected string $instructions = <<<'MARKDOWN'
        This MCP server allows AI agents to create and manage pages in the Laravel application.
        Pages can contain flexible content blocks that can be arranged and customized.
    MARKDOWN;

    /**
     * @var array<int, Tool|class-string<Tool>>
     */
    protected array $tools = [
        CreatePageTool::class,
        UpdatePageTool::class,
        ListPagesTool::class,
        ListBlocksTool::class,
        AddBlocksToPageTool::class,
    ];
}

