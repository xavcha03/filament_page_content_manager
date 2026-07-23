<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Experiences;

use Xavcha\PageContentManager\Models\Page;

class ExperienceDataResolver
{
    public function __construct(
        protected ExperienceRegistry $registry,
    ) {}

    /**
     * Résout le payload API pour l'Experience active d'une page.
     *
     * @return array{key: string, content: array<string, mixed>}|null
     */
    public function resolveForPage(Page $page): ?array
    {
        if (! $page->isExperienceMode()) {
            return null;
        }

        $key = $page->experience_key;
        if (! is_string($key) || $key === '' || ! $this->registry->has($key)) {
            return null;
        }

        /** @var class-string<\Xavcha\PageContentManager\Experiences\Contracts\ExperienceInterface> $class */
        $class = $this->registry->get($key);
        $raw = $page->getActiveExperienceContent();

        return [
            'key' => $key,
            'content' => $class::transform($raw),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function transform(string $key, array $raw): array
    {
        $class = $this->registry->get($key);
        if ($class === null) {
            return $raw;
        }

        return $class::transform($raw);
    }
}
