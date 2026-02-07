<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Mcp\Helpers;

use Xavcha\PageContentManager\Blocks\BlockRegistry;

class BlockDataValidator
{
    public function __construct(
        protected BlockRegistry $registry,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array{ok: bool, error: string|null}
     */
    public function validateBlockData(string $blockType, array $data): array
    {
        if (!$this->registry->has($blockType)) {
            return [
                'ok' => false,
                'error' => "Block type '{$blockType}' does not exist. Use list_blocks to see available blocks.",
            ];
        }

        $blockClass = $this->registry->get($blockType);
        $blockInfo = BlockInfoExtractor::extract($blockType, $blockClass);
        $fields = $blockInfo['fields'] ?? [];

        if (!is_array($fields) || $fields === []) {
            return ['ok' => true, 'error' => null];
        }

        $error = $this->validateObjectAgainstFields($data, $fields, 'data');
        if ($error !== null) {
            return ['ok' => false, 'error' => $error];
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed> $fieldDefs
     */
    protected function validateObjectAgainstFields(array $data, array $fieldDefs, string $path): ?string
    {
        $allowed = [];
        $required = [];
        $fieldMap = [];

        foreach ($fieldDefs as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = $field['name'] ?? ($field['key'] ?? null);
            if (!is_string($name) || $name === '') {
                continue;
            }

            $allowed[] = $name;
            $fieldMap[$name] = $field;

            if (($field['required'] ?? false) === true) {
                $required[] = $name;
            }
        }

        if ($allowed === []) {
            return null;
        }

        $missing = [];
        foreach ($required as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                $missing[] = $requiredKey;
            }
        }

        if ($missing !== []) {
            return "{$path}: required fields missing: " . json_encode($missing, JSON_UNESCAPED_UNICODE);
        }

        $allowedSet = array_flip($allowed);
        $unknown = [];
        foreach (array_keys($data) as $key) {
            if (!isset($allowedSet[$key])) {
                $unknown[] = $key;
            }
        }

        if ($unknown !== []) {
            return "{$path}: unknown fields: " . json_encode($unknown, JSON_UNESCAPED_UNICODE);
        }

        foreach ($data as $key => $value) {
            $fieldDef = $fieldMap[$key] ?? null;
            if (!is_array($fieldDef)) {
                continue;
            }

            $fieldType = strtolower((string) ($fieldDef['type'] ?? ''));
            $nestedFields = $fieldDef['fields'] ?? null;
            $nestedItems = $fieldDef['items'] ?? null;

            if (in_array($fieldType, ['object', 'group'], true) && is_array($nestedFields)) {
                if (!is_array($value)) {
                    return "{$path}.{$key} must be an object.";
                }

                $nestedError = $this->validateObjectAgainstFields($value, $nestedFields, "{$path}.{$key}");
                if ($nestedError !== null) {
                    return $nestedError;
                }
            }

            if (in_array($fieldType, ['array', 'repeater', 'list'], true) && is_array($nestedItems)) {
                if (!is_array($value)) {
                    return "{$path}.{$key} must be an array.";
                }

                foreach ($value as $index => $item) {
                    if (!is_array($item)) {
                        return "{$path}.{$key}[{$index}] must be an object.";
                    }

                    $nestedError = $this->validateObjectAgainstFields(
                        $item,
                        $nestedItems,
                        "{$path}.{$key}[{$index}]"
                    );
                    if ($nestedError !== null) {
                        return $nestedError;
                    }
                }
            }
        }

        return null;
    }
}
