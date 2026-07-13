<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Helpers;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

class BlockMcpSchema
{
    public static function blocksParameter(JsonSchema $schema, string $description): Type
    {
        return $schema->array()
            ->items(
                $schema->object([
                    'type' => $schema->string()->required(),
                    'data' => $schema->object()->required(),
                ])
            )
            ->min(1)
            ->required()
            ->description($description);
    }
}
