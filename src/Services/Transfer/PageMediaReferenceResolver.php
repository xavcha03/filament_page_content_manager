<?php

namespace Xavcha\PageContentManager\Services\Transfer;

use Xavier\MediaLibraryPro\Models\MediaFile;

class PageMediaReferenceResolver
{
    public const REF_PREFIX = 'media:';

    public function isMediaReference(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, self::REF_PREFIX);
    }

    public function extractUuidFromReference(string $reference): string
    {
        return substr($reference, strlen(self::REF_PREFIX));
    }

    public function makeReference(string $uuid): string
    {
        return self::REF_PREFIX . $uuid;
    }

    /**
     * @return list<int>
     */
    public function collectMediaIdsFromContent(array $content): array
    {
        $ids = [];

        $this->walkContent($content, function (string $key, mixed $value) use (&$ids): void {
            if (! $this->isMediaFieldKey($key)) {
                return;
            }

            foreach ($this->normalizeMediaValues($value) as $id) {
                if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                    $ids[] = (int) $id;
                }
            }
        });

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, array<string, mixed>>  $mediaManifest
     */
    public function replaceMediaIdsWithReferences(array $content, array &$mediaManifest): array
    {
        return $this->transformContent($content, function (string $key, mixed $value) use (&$mediaManifest): mixed {
            if (! $this->isMediaFieldKey($key)) {
                return $value;
            }

            $values = $this->normalizeMediaValues($value);
            $isListField = str_ends_with($key, '_ids');

            if ($isListField) {
                $references = [];

                foreach ($values as $id) {
                    if (! is_int($id) && ! (is_string($id) && ctype_digit($id))) {
                        continue;
                    }

                    $mediaFile = MediaFile::find((int) $id);

                    if ($mediaFile === null) {
                        continue;
                    }

                    $mediaManifest[$mediaFile->uuid] = $this->serializeMediaManifestEntry($mediaFile);
                    $references[] = $this->makeReference($mediaFile->uuid);
                }

                return $references;
            }

            $id = $values[0] ?? null;

            if (! is_int($id) && ! (is_string($id) && ctype_digit($id))) {
                return $value;
            }

            $mediaFile = MediaFile::find((int) $id);

            if ($mediaFile === null) {
                return $value;
            }

            $mediaManifest[$mediaFile->uuid] = $this->serializeMediaManifestEntry($mediaFile);

            return $this->makeReference($mediaFile->uuid);
        });
    }

    /**
     * @param  array<string, int>  $uuidToIdMap
     */
    public function replaceMediaReferencesWithIds(array $content, array $uuidToIdMap): array
    {
        return $this->transformContent($content, function (string $key, mixed $value) use ($uuidToIdMap): mixed {
            if (! $this->isMediaFieldKey($key)) {
                return $value;
            }

            $isListField = str_ends_with($key, '_ids');

            if ($isListField) {
                $values = is_array($value) ? $value : $this->normalizeMediaValues($value);
                $ids = [];

                foreach ($values as $entry) {
                    if (! $this->isMediaReference($entry)) {
                        continue;
                    }

                    $uuid = $this->extractUuidFromReference($entry);
                    $ids[] = $uuidToIdMap[$uuid] ?? null;
                }

                return array_values(array_filter($ids, fn ($id) => $id !== null));
            }

            if (! $this->isMediaReference($value)) {
                return $value;
            }

            $uuid = $this->extractUuidFromReference((string) $value);

            return $uuidToIdMap[$uuid] ?? null;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMediaManifestEntry(MediaFile $mediaFile): array
    {
        return [
            'uuid' => $mediaFile->uuid,
            'file_name' => $mediaFile->file_name,
            'mime_type' => $mediaFile->mime_type,
            'alt_text' => $mediaFile->alt_text,
            'description' => $mediaFile->description,
            'width' => $mediaFile->width,
            'height' => $mediaFile->height,
            'size' => $mediaFile->size,
        ];
    }

    protected function isMediaFieldKey(string $key): bool
    {
        return str_ends_with($key, '_id') || str_ends_with($key, '_ids');
    }

    /**
     * @return list<mixed>
     */
    protected function normalizeMediaValues(mixed $value): array
    {
        if ($this->isMediaReference($value)) {
            return [$value];
        }

        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values($decoded);
            }

            if ($value !== '') {
                return [$value];
            }
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return [(int) $value];
        }

        return [];
    }

    /**
     * @param  callable(string, mixed): mixed  $transform
     */
    protected function transformContent(array $content, callable $transform): array
    {
        if (isset($content['sections']) && is_array($content['sections'])) {
            foreach ($content['sections'] as $index => $section) {
                if (! is_array($section) || ! isset($section['data']) || ! is_array($section['data'])) {
                    continue;
                }

                $content['sections'][$index]['data'] = $this->transformData($section['data'], $transform);
            }
        }

        return $content;
    }

    /**
     * @param  callable(string, mixed): void  $callback
     */
    protected function walkContent(array $content, callable $callback): void
    {
        if (isset($content['sections']) && is_array($content['sections'])) {
            foreach ($content['sections'] as $section) {
                if (! is_array($section) || ! isset($section['data']) || ! is_array($section['data'])) {
                    continue;
                }

                $this->walkData($section['data'], $callback);
            }
        }
    }

    /**
     * @param  callable(string, mixed): mixed  $transform
     */
    protected function transformData(array $data, callable $transform): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isMediaFieldKey((string) $key)) {
                    $data[$key] = $transform((string) $key, $value);
                } else {
                    $data[$key] = $this->transformList($value, $transform);
                }

                continue;
            }

            if ($this->isMediaFieldKey((string) $key)) {
                $data[$key] = $transform((string) $key, $value);
            }
        }

        return $data;
    }

    /**
     * @param  callable(string, mixed): mixed  $transform
     */
    protected function transformList(array $items, callable $transform): array
    {
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[$index] = $this->transformData($item, $transform);
        }

        return $items;
    }

    /**
     * @param  callable(string, mixed): void  $callback
     */
    protected function walkData(array $data, callable $callback): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isMediaFieldKey((string) $key)) {
                    $callback((string) $key, $value);
                } else {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $this->walkData($item, $callback);
                        }
                    }
                }

                continue;
            }

            if ($this->isMediaFieldKey((string) $key)) {
                $callback((string) $key, $value);
            }
        }
    }
}
