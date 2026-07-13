<?php

namespace Xavcha\PageContentManager\Services\Transfer;

use Xavier\MediaLibraryPro\Models\MediaFile;
use Xavier\MediaLibraryPro\Services\MediaUploadService;

class PageMediaImporter
{
    public function __construct(
        protected MediaUploadService $mediaUploadService,
    ) {}

    /**
     * @return array<string, int> uuid => local media id
     */
    public function import(PageTransferPackage $package): array
    {
        $map = [];

        foreach ($package->mediaManifest as $uuid => $entry) {
            $existing = MediaFile::query()->where('uuid', $uuid)->first();

            if ($existing !== null) {
                $map[$uuid] = $existing->id;

                continue;
            }

            $sourcePath = $package->mediaFiles[$uuid] ?? null;

            if ($sourcePath === null || ! is_file($sourcePath)) {
                continue;
            }

            $mediaFile = $this->mediaUploadService->uploadFromPath($sourcePath, [
                'name' => (string) ($entry['file_name'] ?? basename($sourcePath)),
            ]);

            $mediaFile->uuid = $uuid;
            $mediaFile->alt_text = $entry['alt_text'] ?? $mediaFile->alt_text;
            $mediaFile->description = $entry['description'] ?? $mediaFile->description;
            $mediaFile->save();

            $map[$uuid] = $mediaFile->id;
        }

        return $map;
    }
}
