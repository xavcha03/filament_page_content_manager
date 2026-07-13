<?php

namespace Xavcha\PageContentManager\Services\Transfer;

class PageTransferPackage
{
    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, array<string, mixed>>  $pages  keyed by slug
     * @param  array<string, array<string, mixed>>  $mediaManifest  keyed by uuid
     * @param  array<string, string>  $mediaFiles  uuid => absolute path in extracted archive
     */
    public function __construct(
        public array $manifest,
        public array $pages,
        public array $mediaManifest,
        public array $mediaFiles,
        public ?string $extractedPath = null,
    ) {}

    public function format(): string
    {
        return (string) ($this->manifest['format'] ?? '');
    }

    public function formatVersion(): int
    {
        return (int) ($this->manifest['format_version'] ?? 0);
    }

    /**
     * @return list<string>
     */
    public function pageSlugs(): array
    {
        return array_values($this->manifest['pages'] ?? array_keys($this->pages));
    }
}
