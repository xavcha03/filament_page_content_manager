<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\CustomAddBlocksToPageTool;
use Laravel\Mcp\Tool;
use Xavcha\PageContentManager\Mcp\PageMcpServer;

class BaseSiteServer extends PageMcpServer
{
    protected string $name = 'Base Site MCP Server';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        This MCP server allows AI agents to create and manage pages in the Laravel application.
        Pages can contain flexible content blocks that can be arranged and customized.
        This server includes all standard tools plus a custom tool to modify the home page.
    MARKDOWN;

    /**
     * @var array<int, Tool|class-string<Tool>>
     */
    protected array $tools = self::mergeTools([
        CustomAddBlocksToPageTool::class,
    ]);
}
