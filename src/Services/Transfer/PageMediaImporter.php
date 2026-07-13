<?php

namespace Xavcha\PageContentManager\Services\Transfer;

use Illuminate\Support\Facades\Schema;
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
            $mediaId = $this->importMedia(
                $uuid,
                $entry,
                $package->mediaFiles[$uuid] ?? null,
            );

            if ($mediaId !== null) {
                $map[$uuid] = $mediaId;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function importMedia(string $uuid, array $entry, ?string $sourcePath): ?int
    {
        $existing = $this->findByUuid($uuid);

        if ($existing !== null) {
            $this->restoreIfTrashed($existing);
            $this->updateMediaMetadata($existing, $entry);

            return $existing->id;
        }

        if ($sourcePath === null || ! is_file($sourcePath)) {
            return null;
        }

        $existingByChecksum = $this->findBySourceChecksum($sourcePath);

        if ($existingByChecksum !== null) {
            $this->restoreIfTrashed($existingByChecksum);
            $this->updateMediaMetadata($existingByChecksum, $entry);

            return $existingByChecksum->id;
        }

        $mediaFile = $this->mediaUploadService->uploadFromPath($sourcePath, [
            'name' => (string) ($entry['file_name'] ?? basename($sourcePath)),
        ]);

        $existingAfterUpload = $this->findByUuid($uuid);

        if ($existingAfterUpload !== null && $existingAfterUpload->id !== $mediaFile->id) {
            $this->restoreIfTrashed($existingAfterUpload);
            $this->updateMediaMetadata($existingAfterUpload, $entry);

            return $existingAfterUpload->id;
        }

        if ($this->wasRecentlyCreated($mediaFile) && ! $this->uuidExists($uuid)) {
            $mediaFile->uuid = $uuid;
        }

        $this->updateMediaMetadata($mediaFile, $entry);
        $mediaFile->save();

        return $mediaFile->id;
    }

    protected function findByUuid(string $uuid): ?MediaFile
    {
        return MediaFile::query()
            ->withTrashed()
            ->where('uuid', $uuid)
            ->first();
    }

    protected function uuidExists(string $uuid): bool
    {
        return MediaFile::query()
            ->withTrashed()
            ->where('uuid', $uuid)
            ->exists();
    }

    protected function findBySourceChecksum(string $sourcePath): ?MediaFile
    {
        if (! Schema::hasColumn('media_files', 'checksum') || ! is_file($sourcePath)) {
            return null;
        }

        $checksum = hash_file('sha256', $sourcePath);

        if ($checksum === false) {
            return null;
        }

        $disk = config('media-library-pro.storage.disk', 'public');

        return MediaFile::query()
            ->withTrashed()
            ->where('disk', $disk)
            ->where('checksum', $checksum)
            ->first();
    }

    protected function wasRecentlyCreated(MediaFile $mediaFile): bool
    {
        if ($mediaFile->created_at === null) {
            return false;
        }

        return $mediaFile->created_at->greaterThanOrEqualTo(now()->subSeconds(10));
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function updateMediaMetadata(MediaFile $mediaFile, array $entry): void
    {
        if (array_key_exists('alt_text', $entry)) {
            $mediaFile->alt_text = $entry['alt_text'];
        }

        if (array_key_exists('description', $entry)) {
            $mediaFile->description = $entry['description'];
        }

        if ($mediaFile->isDirty(['alt_text', 'description'])) {
            $mediaFile->save();
        }
    }

    protected function restoreIfTrashed(MediaFile $mediaFile): void
    {
        if ($mediaFile->trashed()) {
            $mediaFile->restore();
        }
    }
}
