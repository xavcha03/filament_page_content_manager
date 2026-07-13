<?php

namespace Xavcha\PageContentManager\Services\Transfer;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Xavcha\PageContentManager\Models\Page;
use Xavier\MediaLibraryPro\Models\MediaFile;

class PageExporter
{
    public function __construct(
        protected PagePackageArchive $archive,
        protected PageMediaReferenceResolver $mediaReferenceResolver,
    ) {}

    /**
     * @param  Collection<int, Page>|array<int, Page>  $pages
     */
    public function exportToFile(Collection | array $pages, ?string $destinationPath = null): string
    {
        $pages = $pages instanceof Collection ? $pages->all() : $pages;

        if ($pages === []) {
            throw new \InvalidArgumentException('Aucune page à exporter.');
        }

        $serializedPages = [];
        $mediaManifest = [];
        $mediaSourcePaths = [];
        $slugs = [];

        foreach ($pages as $page) {
            if (! $page instanceof Page) {
                continue;
            }

            $serialized = $this->serializePage($page);
            $serialized['content'] = $this->mediaReferenceResolver->replaceMediaIdsWithReferences(
                $serialized['content'],
                $mediaManifest,
            );

            $slug = (string) $page->slug;
            $serializedPages[$slug] = $serialized;
            $slugs[] = $slug;
        }

        foreach (array_keys($mediaManifest) as $uuid) {
            $mediaFile = MediaFile::query()->where('uuid', $uuid)->first();

            if ($mediaFile === null) {
                unset($mediaManifest[$uuid]);

                continue;
            }

            $sourcePath = $this->resolveMediaSourcePath($mediaFile);

            if ($sourcePath !== null) {
                $mediaSourcePaths[$uuid] = $sourcePath;
            }
        }

        $manifest = [
            'format' => 'xavcha-page',
            'format_version' => (int) config('page-content-manager.transfer.format_version', 1),
            'exported_at' => now()->toIso8601String(),
            'source' => [
                'app_url' => (string) config('app.url'),
                'package_version' => (string) config('page-content-manager.transfer.version', '0.3.0'),
            ],
            'pages' => $slugs,
        ];

        $destinationPath ??= $this->defaultDestinationPath(count($pages) === 1 ? $pages[0] : null, $slugs);

        return $this->archive->create(
            manifest: $manifest,
            pages: $serializedPages,
            mediaManifest: $mediaManifest,
            mediaSourcePaths: $mediaSourcePaths,
            destinationPath: $destinationPath,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function serializePage(Page $page): array
    {
        return [
            'type' => $page->type,
            'slug' => $page->slug,
            'title' => $page->title,
            'status' => $page->status,
            'published_at' => $page->published_at?->toIso8601String(),
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'seo_noindex' => (bool) $page->seo_noindex,
            'content' => $page->content ?? [
                'sections' => [],
                'metadata' => ['schema_version' => 1],
            ],
        ];
    }

    /**
     * @param  list<string>  $slugs
     */
    protected function defaultDestinationPath(?Page $singlePage, array $slugs): string
    {
        $extension = (string) config('page-content-manager.transfer.extension', 'xavcha-page.zip');
        $baseName = $singlePage !== null
            ? Str::slug((string) $singlePage->slug)
            : 'pages-export-' . now()->format('Y-m-d-His');

        return sys_get_temp_dir() . '/' . $baseName . '.' . ltrim($extension, '.');
    }

    protected function resolveMediaSourcePath(MediaFile $mediaFile): ?string
    {
        try {
            $path = $mediaFile->getPath();

            return is_file($path) ? $path : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
