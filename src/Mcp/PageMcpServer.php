<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp;

use Laravel\Mcp\Server;
use Xavcha\PageContentManager\Mcp\Tools\AddBlocksToPageTool;
use Xavcha\PageContentManager\Mcp\Tools\CreatePageTool;
use Xavcha\PageContentManager\Mcp\Tools\DeleteBlockTool;
use Xavcha\PageContentManager\Mcp\Tools\DeletePageTool;
use Xavcha\PageContentManager\Mcp\Tools\DuplicatePageTool;
use Xavcha\PageContentManager\Mcp\Tools\GetBlockSchemaTool;
use Xavcha\PageContentManager\Mcp\Tools\GetPageContentTool;
use Xavcha\PageContentManager\Mcp\Tools\ListBlocksTool;
use Xavcha\PageContentManager\Mcp\Tools\ListPagesTool;
use Xavcha\PageContentManager\Mcp\Tools\ReorderBlocksTool;
use Xavcha\PageContentManager\Mcp\Tools\UpdateBlockTool;
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
     * Tools du package (pages + blocs).
     *
     * @return array<int, \Laravel\Mcp\Tool|class-string<\Laravel\Mcp\Tool>>
     */
    public static function getTools(): array
    {
        return [
            // Pages
            CreatePageTool::class,
            UpdatePageTool::class,
            ListPagesTool::class,
            GetPageContentTool::class,
            DeletePageTool::class,
            DuplicatePageTool::class,
            // Blocs
            ListBlocksTool::class,
            GetBlockSchemaTool::class,
            AddBlocksToPageTool::class,
            UpdateBlockTool::class,
            DeleteBlockTool::class,
            ReorderBlocksTool::class,
        ];
    }

    /**
     * Merge tools du package + custom.
     *
     * @param array<int, \Laravel\Mcp\Tool|class-string<\Laravel\Mcp\Tool>> $customTools
     * @return array<int, \Laravel\Mcp\Tool|class-string<\Laravel\Mcp\Tool>>
     */
    public static function mergeTools(array $customTools): array
    {
        return array_merge(self::getTools(), $customTools);
    }

    /**
     * @var array<int, \Laravel\Mcp\Tool|class-string<\Laravel\Mcp\Tool>>
     */
    protected array $tools = [];

    /**
     * Boot the server and initialize tools.
     */
    protected function boot(): void
    {
        parent::boot();
        $this->tools = self::getTools();
    }
}

