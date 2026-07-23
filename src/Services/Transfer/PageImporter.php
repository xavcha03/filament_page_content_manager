<?php

namespace Xavcha\PageContentManager\Services\Transfer;

use Xavcha\PageContentManager\Models\Page;

class PageImporter
{
    public function __construct(
        protected PageMediaReferenceResolver $mediaReferenceResolver,
        protected PageMediaImporter $mediaImporter,
    ) {}

    /**
     * @param  array{
     *     on_conflict?: 'replace'|'skip',
     *     import_as_draft?: bool
     * }  $options
     * @return list<Page>
     */
    public function import(PageTransferPackage $package, array $options = []): array
    {
        $onConflict = $options['on_conflict'] ?? 'replace';
        $importAsDraft = (bool) ($options['import_as_draft'] ?? config('page-content-manager.transfer.import_force_draft_default', true));

        $uuidToIdMap = $this->mediaImporter->import($package);
        $importedPages = [];

        foreach ($package->pages as $slug => $pageData) {
            $existingPage = $this->findExistingPage((string) ($pageData['type'] ?? 'standard'), $slug);

            if ($existingPage !== null && $onConflict === 'skip') {
                continue;
            }

            $attributes = $this->buildPageAttributes($pageData, $uuidToIdMap, $importAsDraft);

            if ($existingPage !== null) {
                if ($existingPage->trashed()) {
                    $existingPage->restore();
                }

                $existingPage->fill($attributes);
                $existingPage->save();
                $importedPages[] = $existingPage->fresh();

                continue;
            }

            $importedPages[] = Page::create($attributes);
        }

        return $importedPages;
    }

    /**
     * @param  array<string, int>  $uuidToIdMap
     * @return array<string, mixed>
     */
    protected function buildPageAttributes(array $pageData, array $uuidToIdMap, bool $importAsDraft): array
    {
        $content = $pageData['content'] ?? [
            'sections' => [],
            'metadata' => ['schema_version' => 1],
        ];

        if (is_array($content)) {
            $content = $this->mediaReferenceResolver->replaceMediaReferencesWithIds($content, $uuidToIdMap);
        }

        $experienceContent = $pageData['experience_content'] ?? [];
        if (is_array($experienceContent)) {
            $experienceContent = $this->mediaReferenceResolver->replaceMediaReferencesWithIds($experienceContent, $uuidToIdMap);
        }

        $contentMode = (string) ($pageData['content_mode'] ?? Page::CONTENT_MODE_BLOCKS);
        if (! in_array($contentMode, [Page::CONTENT_MODE_BLOCKS, Page::CONTENT_MODE_EXPERIENCE], true)) {
            $contentMode = Page::CONTENT_MODE_BLOCKS;
        }

        $experienceKey = $pageData['experience_key'] ?? null;
        if ($contentMode === Page::CONTENT_MODE_BLOCKS) {
            // Keep key/bag for round-trip but do not require registry when importing as blocks
        }

        $attributes = [
            'type' => (string) ($pageData['type'] ?? 'standard'),
            'slug' => (string) ($pageData['slug'] ?? ''),
            'title' => (string) ($pageData['title'] ?? ''),
            'content_mode' => $contentMode,
            'experience_key' => is_string($experienceKey) && $experienceKey !== '' ? $experienceKey : null,
            'experience_content' => is_array($experienceContent) ? $experienceContent : [],
            'content' => $content,
            'seo_title' => $pageData['seo_title'] ?? null,
            'seo_description' => $pageData['seo_description'] ?? null,
            'seo_noindex' => (bool) ($pageData['seo_noindex'] ?? false),
            'status' => (string) ($pageData['status'] ?? 'draft'),
            'published_at' => $pageData['published_at'] ?? null,
        ];

        // If experience mode but key unknown in this app, fall back to blocks to avoid import failure
        if (
            $attributes['content_mode'] === Page::CONTENT_MODE_EXPERIENCE
            && (
                ! is_string($attributes['experience_key'])
                || $attributes['experience_key'] === ''
                || ! app(\Xavcha\PageContentManager\Experiences\ExperienceRegistry::class)->has($attributes['experience_key'])
            )
        ) {
            $attributes['content_mode'] = Page::CONTENT_MODE_BLOCKS;
        }

        if ($importAsDraft) {
            $attributes['status'] = 'draft';
            $attributes['published_at'] = null;
        }

        return $attributes;
    }

    protected function findExistingPage(string $type, string $slug): ?Page
    {
        if ($type === 'home') {
            return Page::query()->withTrashed()->where('type', 'home')->first();
        }

        return Page::query()->withTrashed()->where('slug', $slug)->first();
    }
}
