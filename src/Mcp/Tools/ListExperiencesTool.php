<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Experiences\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Experiences\ExperienceRegistry;

class ListExperiencesTool extends Tool
{
    protected string $name = 'list_experiences';

    protected string $title = 'List Experiences';

    protected string $description = 'Lists available Experience page templates (fixed schemas defined in code under app/Experiences). Use this before get_experience_schema or update_experience_fields. Experiences have a fixed structure — you can only edit field values, never add/remove/reorder fields.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        try {
            $registry = app(ExperienceRegistry::class);
            $items = [];

            foreach ($registry->all() as $key => $class) {
                $item = [
                    'key' => $key,
                    'label' => $class::getLabel(),
                ];

                $usesMcp = in_array(HasMcpMetadata::class, class_uses_recursive($class), true);
                if ($usesMcp || method_exists($class, 'getMcpDescription')) {
                    try {
                        $item['description'] = $class::getMcpDescription();
                    } catch (\Throwable) {
                        // ignore
                    }
                }

                $items[] = $item;
            }

            return Response::json([
                'success' => true,
                'experiences' => $items,
                'count' => count($items),
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to list experiences: ' . $e->getMessage());
        }
    }
}
