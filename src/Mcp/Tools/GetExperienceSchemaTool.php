<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Xavcha\PageContentManager\Experiences\ExperienceRegistry;

class GetExperienceSchemaTool extends Tool
{
    protected string $name = 'get_experience_schema';

    protected string $title = 'Get Experience Schema';

    protected string $description = 'Returns the editable field schema for a fixed Experience template (key, label, fields, example). Structure is defined in code and cannot be changed via MCP. Use list_experiences first, then update_experience_fields with only the field names listed here.';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()->description('Experience key (e.g. "home-organic"). Use list_experiences.'),
        ];
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
        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        try {
            $registry = app(ExperienceRegistry::class);
            $key = $validated['key'];

            if (! $registry->has($key)) {
                return Response::error("Experience key '{$key}' does not exist. Use list_experiences.");
            }

            $class = $registry->get($key);

            $fields = method_exists($class, 'getMcpFields') ? $class::getMcpFields() : [];
            $example = method_exists($class, 'getMcpExample') ? $class::getMcpExample() : null;
            $description = method_exists($class, 'getMcpDescription')
                ? $class::getMcpDescription()
                : $class::getLabel();
            $metadata = method_exists($class, 'getMcpMetadata') ? $class::getMcpMetadata() : [];

            return Response::json([
                'success' => true,
                'experience' => [
                    'key' => $key,
                    'label' => $class::getLabel(),
                    'description' => $description,
                    'fields' => $fields,
                    'example' => $example,
                    'metadata' => $metadata,
                    'structure_editable' => false,
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to get experience schema: ' . $e->getMessage());
        }
    }
}
