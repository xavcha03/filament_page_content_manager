<?php

namespace Xavcha\PageContentManager\Services\Blocks\Transformers\Core;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;

class ContactFormBlockTransformer implements BlockTransformerInterface
{
    public function getType(): string
    {
        return 'contact_form';
    }

    public function transform(array $data): array
    {
        return [
            'type' => 'contact_form',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'success_message' => $data['success_message'] ?? '',
        ];
    }
}

