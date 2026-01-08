<?php

namespace Xavcha\PageContentManager\Services\Blocks\Transformers\Core;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;
use Xavier\MediaLibraryPro\Models\MediaFile;

class ImageBlockTransformer implements BlockTransformerInterface
{
    public function getType(): string
    {
        return 'image';
    }

    public function transform(array $data): array
    {
        $transformed = [
            'type' => 'image',
            'alt' => $data['alt'] ?? '',
            'caption' => $data['caption'] ?? '',
        ];

        if (!empty($data['image_id'])) {
            $imageUrl = $this->getMediaFileUrl($data['image_id']);
            if ($imageUrl) {
                $transformed['image_url'] = $imageUrl;
            }
        }

        return $transformed;
    }

    protected function getMediaFileUrl($mediaFileId): ?string
    {
        if (empty($mediaFileId)) {
            return null;
        }

        $mediaFile = MediaFile::find($mediaFileId);
        if (!$mediaFile) {
            return null;
        }

        return route('media-library-pro.serve', [
            'media' => $mediaFile->uuid
        ]);
    }
}

