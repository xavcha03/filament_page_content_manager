<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Filament\Resources\Pages\Concerns;

use Xavcha\PageContentManager\Models\Page;

trait HandlesExperienceFormState
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hydrateExperienceFields(array $data): array
    {
        $key = $data['experience_key'] ?? null;
        $bag = $data['experience_content'] ?? [];
        if (! is_array($bag)) {
            $bag = [];
        }

        $data['experience_content'] = $bag;
        $data['experience_fields'] = (
            is_string($key)
            && $key !== ''
            && isset($bag[$key])
            && is_array($bag[$key])
        ) ? $bag[$key] : [];

        if (! isset($data['content_mode']) || $data['content_mode'] === null || $data['content_mode'] === '') {
            $data['content_mode'] = Page::CONTENT_MODE_BLOCKS;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function dehydrateExperienceFields(array $data): array
    {
        $key = $data['experience_key'] ?? null;
        $fields = $data['experience_fields'] ?? [];
        unset($data['experience_fields']);

        $bag = is_array($data['experience_content'] ?? null) ? $data['experience_content'] : [];

        if (is_string($key) && $key !== '') {
            $bag[$key] = is_array($fields) ? $fields : [];
        }

        $data['experience_content'] = $bag;

        if (($data['content_mode'] ?? Page::CONTENT_MODE_BLOCKS) === Page::CONTENT_MODE_BLOCKS) {
            // Conserver experience_key / bag pour pouvoir revenir sans perte
        }

        return $data;
    }
}
