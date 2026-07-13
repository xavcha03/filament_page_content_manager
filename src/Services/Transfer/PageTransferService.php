<?php

namespace Xavcha\PageContentManager\Services\Transfer;

use Illuminate\Database\Eloquent\Collection;
use Xavcha\PageContentManager\Models\Page;

class PageTransferService
{
    public function __construct(
        protected PageExporter $exporter,
        protected PageImporter $importer,
        protected PagePackageArchive $archive,
        protected PageTransferValidator $validator,
    ) {}

    /**
     * @param  Collection<int, Page>|array<int, Page>  $pages
     */
    public function exportToFile(Collection | array $pages, ?string $destinationPath = null): string
    {
        return $this->exporter->exportToFile($pages, $destinationPath);
    }

    public function readPackage(string $archivePath): PageTransferPackage
    {
        return $this->archive->read($archivePath);
    }

    /**
     * @return array<string, mixed>
     */
    public function previewFromPath(string $archivePath): array
    {
        $package = $this->readPackage($archivePath);

        try {
            return $this->validator->preview($package);
        } finally {
            $this->archive->cleanup($package);
        }
    }

    /**
     * @param  array{
     *     on_conflict?: 'replace'|'skip',
     *     import_as_draft?: bool
     * }  $options
     * @return array{pages: list<Page>, preview: array<string, mixed>}
     */
    public function importFromPath(string $archivePath, array $options = []): array
    {
        $package = $this->readPackage($archivePath);
        $preview = $this->validator->preview($package);

        if (! $preview['valid']) {
            $this->archive->cleanup($package);

            throw new \RuntimeException(implode(' ', $preview['errors']));
        }

        try {
            $pages = $this->importer->import($package, $options);

            return [
                'pages' => $pages,
                'preview' => $preview,
            ];
        } finally {
            $this->archive->cleanup($package);
        }
    }

    public function cleanup(PageTransferPackage $package): void
    {
        $this->archive->cleanup($package);
    }
}
