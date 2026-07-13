<?php

namespace Xavcha\PageContentManager\Tests\Unit\Mcp;

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Xavcha\PageContentManager\Mcp\Helpers\BlockMcpSchema;
use Xavcha\PageContentManager\Mcp\Tools\AddBlocksToPageTool;
use Xavcha\PageContentManager\Mcp\Tools\CreatePageWithBlocksTool;
use Xavcha\PageContentManager\Tests\TestCase;

class BlockMcpSchemaTest extends TestCase
{
    public function test_blocks_parameter_declares_typed_object_items(): void
    {
        $schema = new JsonSchemaTypeFactory();
        $blocks = BlockMcpSchema::blocksParameter($schema, 'Test blocks');

        $encoded = json_encode($blocks->toArray(), JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('array', $decoded['type']);
        $this->assertSame(1, $decoded['minItems']);
        $this->assertSame('object', $decoded['items']['type']);
        $this->assertArrayHasKey('type', $decoded['items']['properties']);
        $this->assertArrayHasKey('data', $decoded['items']['properties']);
        $this->assertContains('type', $decoded['items']['required']);
        $this->assertContains('data', $decoded['items']['required']);
    }

    public function test_add_blocks_tool_exposes_typed_blocks_schema(): void
    {
        $tool = new AddBlocksToPageTool();
        $schema = $tool->schema(new JsonSchemaTypeFactory());

        $this->assertArrayHasKey('blocks', $schema);
        $this->assertSame('array', $schema['blocks']->toArray()['type']);
        $this->assertSame('object', $schema['blocks']->toArray()['items']['type']);
    }

    public function test_create_page_with_blocks_tool_exposes_typed_blocks_schema(): void
    {
        $tool = new CreatePageWithBlocksTool();
        $schema = $tool->schema(new JsonSchemaTypeFactory());

        $this->assertArrayHasKey('blocks', $schema);
        $this->assertSame('array', $schema['blocks']->toArray()['type']);
        $this->assertSame('object', $schema['blocks']->toArray()['items']['type']);
    }
}
