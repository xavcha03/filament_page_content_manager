<?php

namespace Xavcha\PageContentManager\Services\Transfer;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class PagePackageArchive
{
    public function __construct(
        protected PageMediaReferenceResolver $mediaReferenceResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, array<string, mixed>>  $pages
     * @param  array<string, array<string, mixed>>  $mediaManifest
     * @param  array<string, string>  $mediaSourcePaths  uuid => absolute source file path
     */
    public function create(
        array $manifest,
        array $pages,
        array $mediaManifest,
        array $mediaSourcePaths,
        string $destinationPath,
    ): string {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('L\'extension PHP zip (ZipArchive) est requise pour exporter des pages.');
        }

        $directory = dirname($destinationPath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (File::exists($destinationPath)) {
            File::delete($destinationPath);
        }

        $zip = new ZipArchive();

        if ($zip->open($destinationPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Impossible de créer l'archive : {$destinationPath}");
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        foreach ($pages as $slug => $pageData) {
            $zip->addFromString(
                "pages/{$slug}/page.json",
                json_encode($pageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        }

        if ($mediaManifest !== []) {
            $zip->addFromString(
                'media/manifest.json',
                json_encode(array_values($mediaManifest), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        }

        foreach ($mediaSourcePaths as $uuid => $sourcePath) {
            if (! is_file($sourcePath)) {
                continue;
            }

            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
            $archiveName = 'media/' . $uuid . ($extension !== '' ? '.' . $extension : '');
            $zip->addFile($sourcePath, $archiveName);
        }

        $zip->close();

        return $destinationPath;
    }

    public function read(string $archivePath): PageTransferPackage
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('L\'extension PHP zip (ZipArchive) est requise pour importer des pages.');
        }

        if (! is_file($archivePath)) {
            throw new RuntimeException("Archive introuvable : {$archivePath}");
        }

        $extractedPath = $this->extractToTemporaryDirectory($archivePath);

        $manifestPath = $extractedPath . '/manifest.json';

        if (! is_file($manifestPath)) {
            throw new RuntimeException('Archive invalide : manifest.json manquant.');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest)) {
            throw new RuntimeException('Archive invalide : manifest.json illisible.');
        }

        $pages = [];
        $pagesDirectory = $extractedPath . '/pages';

        if (File::isDirectory($pagesDirectory)) {
            foreach (File::directories($pagesDirectory) as $pageDirectory) {
                $pageFile = $pageDirectory . '/page.json';

                if (! is_file($pageFile)) {
                    continue;
                }

                $pageData = json_decode((string) file_get_contents($pageFile), true);

                if (! is_array($pageData)) {
                    continue;
                }

                $slug = (string) ($pageData['slug'] ?? basename($pageDirectory));
                $pages[$slug] = $pageData;
            }
        }

        $mediaManifest = [];
        $mediaManifestPath = $extractedPath . '/media/manifest.json';

        if (is_file($mediaManifestPath)) {
            $decoded = json_decode((string) file_get_contents($mediaManifestPath), true);

            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (! is_array($entry) || empty($entry['uuid'])) {
                        continue;
                    }

                    $mediaManifest[(string) $entry['uuid']] = $entry;
                }
            }
        }

        $mediaFiles = [];
        $mediaDirectory = $extractedPath . '/media';

        if (File::isDirectory($mediaDirectory)) {
            foreach (File::files($mediaDirectory) as $file) {
                if ($file->getFilename() === 'manifest.json') {
                    continue;
                }

                $uuid = $file->getFilenameWithoutExtension();
                $mediaFiles[$uuid] = $file->getPathname();
            }
        }

        return new PageTransferPackage(
            manifest: $manifest,
            pages: $pages,
            mediaManifest: $mediaManifest,
            mediaFiles: $mediaFiles,
            extractedPath: $extractedPath,
        );
    }

    public function cleanup(PageTransferPackage $package): void
    {
        if ($package->extractedPath !== null && File::isDirectory($package->extractedPath)) {
            File::deleteDirectory($package->extractedPath);
        }
    }

    protected function extractToTemporaryDirectory(string $archivePath): string
    {
        $extractedPath = sys_get_temp_dir() . '/xavcha-page-import-' . uniqid('', true);

        File::makeDirectory($extractedPath, 0755, true);

        $zip = new ZipArchive();

        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException("Impossible d'ouvrir l'archive : {$archivePath}");
        }

        $zip->extractTo($extractedPath);
        $zip->close();

        return $extractedPath;
    }
}
