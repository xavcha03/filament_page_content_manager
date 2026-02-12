<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp;

use Illuminate\Container\Container;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;
use Xavcha\PageContentManager\Mcp\MenuTools\AddMainMenuLinkTool;
use Xavcha\PageContentManager\Mcp\MenuTools\DeleteMainMenuLinkTool;
use Xavcha\PageContentManager\Mcp\MenuTools\GetMainMenuTool;
use Xavcha\PageContentManager\Mcp\MenuTools\ListMainMenuTool;
use Xavcha\PageContentManager\Mcp\MenuTools\MoveMainMenuLinkTool;
use Xavcha\PageContentManager\Mcp\MenuTools\ReorderMainMenuLinksTool;
use Xavcha\PageContentManager\Mcp\MenuTools\ReplaceMainMenuLinksTool;
use Xavcha\PageContentManager\Mcp\MenuTools\UpdateMainMenuLinkTool;
use Xavcha\PageContentManager\Mcp\MenuTools\UpsertMainMenuLinkTool;
use Xavcha\PageContentManager\Mcp\Tools\AddBlocksToPageTool;
use Xavcha\PageContentManager\Mcp\Tools\CreatePageWithBlocksTool;
use Xavcha\PageContentManager\Mcp\Tools\CreatePageTool;
use Xavcha\PageContentManager\Mcp\Tools\DeleteBlockTool;
use Xavcha\PageContentManager\Mcp\Tools\DeletePageTool;
use Xavcha\PageContentManager\Mcp\Tools\DuplicatePageTool;
use Xavcha\PageContentManager\Mcp\Tools\GetBlockSchemaTool;
use Xavcha\PageContentManager\Mcp\Tools\GetPageContentTool;
use Xavcha\PageContentManager\Mcp\Tools\ListBlocksTool;
use Xavcha\PageContentManager\Mcp\Tools\ListPagesTool;
use Xavcha\PageContentManager\Mcp\Tools\ReorderBlocksTool;
use Xavcha\PageContentManager\Mcp\Tools\UpdateBlockFieldsTool;
use Xavcha\PageContentManager\Mcp\Tools\UpdateBlockTool;
use Xavcha\PageContentManager\Mcp\Tools\UpdatePageTool;

class PageMcpServer extends Server
{
    protected string $name = 'Page Content Manager MCP Server';

    protected string $version = '0.2.4';

    protected string $instructions = <<<'MARKDOWN'
        This MCP server allows AI agents to create and manage pages in the Laravel application.
        Pages can contain flexible content blocks that can be arranged and customized.
        Optionally, when enabled in config, menu tools are also available to manage main navigation links.
    MARKDOWN;

    /**
     * Tools du package (pages + blocs).
     *
     * @return array<int, \Laravel\Mcp\Tool|class-string<\Laravel\Mcp\Tool>>
     */
    public static function getTools(): array
    {
        $tools = [
            // Pages
            CreatePageTool::class,
            CreatePageWithBlocksTool::class,
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
            UpdateBlockFieldsTool::class,
            DeleteBlockTool::class,
            ReorderBlocksTool::class,
        ];

        if ((bool) config('page-content-manager.menu.enabled', false)) {
            $tools = array_merge($tools, [
                // Main menu (optional)
                ListMainMenuTool::class,
                GetMainMenuTool::class,
                AddMainMenuLinkTool::class,
                UpsertMainMenuLinkTool::class,
                UpdateMainMenuLinkTool::class,
                DeleteMainMenuLinkTool::class,
                ReorderMainMenuLinksTool::class,
                MoveMainMenuLinkTool::class,
                ReplaceMainMenuLinksTool::class,
            ]);
        }

        return $tools;
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

    /**
     * Build tool request with merged arguments so clients can send either
     * params.arguments = { id, title } or params = { name, id, title }.
     */
    protected function runMethodHandle(JsonRpcRequest $request, ServerContext $context): iterable|JsonRpcResponse
    {
        $container = Container::getInstance();

        /** @var \Laravel\Mcp\Server\Contracts\Method $methodClass */
        $methodClass = $container->make($this->methods[$request->method]);

        $args = $request->params['arguments'] ?? [];
        if (! is_array($args)) {
            $args = [];
        }
        $rest = $request->params;
        unset($rest['arguments'], $rest['name'], $rest['_meta']);
        $merged = array_merge($rest, $args);

        $container->instance('mcp.request', new Request(
            $merged,
            $request->sessionId,
            $request->meta()
        ));

        try {
            $response = $methodClass->handle($request, $context);
        } finally {
            $container->forgetInstance('mcp.request');
        }

        return $response;
    }
}
