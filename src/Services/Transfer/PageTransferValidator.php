<?php

namespace Xavcha\PageContentManager\Services\Transfer;

use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Models\Page;
use Xavier\MediaLibraryPro\Models\MediaFile;

class PageTransferValidator
{
    public function __construct(
        protected BlockRegistry $blockRegistry,
        protected PageMediaReferenceResolver $mediaReferenceResolver,
    ) {}

    /**
     * @return array{
     *     valid: bool,
     *     pages: list<array<string, mixed>>,
     *     media: array{to_import: int, existing: int, missing: list<string>},
     *     errors: list<string>,
     *     warnings: list<string>
     * }
     */
    public function preview(PageTransferPackage $package): array
    {
        $errors = [];
        $warnings = [];
        $pagesPreview = [];
        $mediaToImport = 0;
        $mediaExisting = 0;
        $mediaMissing = [];

        if ($package->format() !== 'xavcha-page') {
            $errors[] = 'Format d\'archive non reconnu.';
        }

        $expectedVersion = (int) config('page-content-manager.transfer.format_version', 1);

        if ($package->formatVersion() !== $expectedVersion) {
            $warnings[] = "Version de format {$package->formatVersion()} différente de la version attendue ({$expectedVersion}).";
        }

        if ($package->pages === []) {
            $errors[] = 'Aucune page trouvée dans l\'archive.';
        }

        foreach ($package->pages as $slug => $pageData) {
            $pagesPreview[] = $this->previewPage($slug, $pageData, $warnings, $errors);
        }

        foreach ($package->mediaManifest as $uuid => $entry) {
            if (MediaFile::query()->where('uuid', $uuid)->exists()) {
                $mediaExisting++;

                continue;
            }

            if (! isset($package->mediaFiles[$uuid])) {
                $mediaMissing[] = $uuid;
                $warnings[] = "Fichier média manquant dans l'archive pour l'UUID {$uuid}.";

                continue;
            }

            $mediaToImport++;
        }

        return [
            'valid' => $errors === [],
            'pages' => $pagesPreview,
            'media' => [
                'to_import' => $mediaToImport,
                'existing' => $mediaExisting,
                'missing' => $mediaMissing,
            ],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    protected function previewPage(string $slug, array $pageData, array &$warnings, array &$errors): array
    {
        $type = (string) ($pageData['type'] ?? 'standard');
        $title = (string) ($pageData['title'] ?? $slug);
        $sections = $pageData['content']['sections'] ?? [];
        $blockCount = is_array($sections) ? count($sections) : 0;

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $blockType = $section['type'] ?? null;

            if (! is_string($blockType) || $blockType === '') {
                $warnings[] = "Section sans type détectée dans la page « {$title} ».";

                continue;
            }

            if ($this->blockRegistry->get($blockType) === null) {
                $warnings[] = "Bloc « {$blockType} » inconnu sur cet environnement (page « {$title} »).";
            }
        }

        $existingPage = $this->findExistingPage($type, $slug);
        $action = $existingPage !== null ? 'replace' : 'create';

        if ($type === 'home' && $existingPage === null) {
            $errors[] = 'La page Home est absente de cet environnement : import impossible.';
        }

        return [
            'slug' => $slug,
            'title' => $title,
            'type' => $type,
            'block_count' => $blockCount,
            'action' => $action,
            'existing_title' => $existingPage?->title,
            'existing_id' => $existingPage?->id,
        ];
    }

    protected function findExistingPage(string $type, string $slug): ?Page
    {
        if ($type === 'home') {
            return Page::query()->withTrashed()->where('type', 'home')->first();
        }

        return Page::query()->withTrashed()->where('slug', $slug)->first();
    }
}
