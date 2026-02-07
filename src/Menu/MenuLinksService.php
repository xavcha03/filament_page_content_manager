<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Menu;

use InvalidArgumentException;
use Xavcha\PageContentManager\Menu\Contracts\MenuLinksStore;

class MenuLinksService
{
    public function __construct(private readonly MenuLinksStore $store) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return array_map(
            fn (array $link): array => $this->normalizeLink($link),
            $this->store->getLinks()
        );
    }

    /**
     * @param array<int, array<string, mixed>> $links
     */
    public function replaceAll(array $links): array
    {
        $normalized = array_map(
            fn (array $link): array => $this->normalizeLink($link),
            $links
        );

        $this->store->saveLinks($normalized);

        return $normalized;
    }

    public function add(array $link, ?int $position = null): array
    {
        $links = $this->all();
        $normalized = $this->normalizeLink($link);

        if ($position === null || $position >= count($links)) {
            $links[] = $normalized;
            $index = count($links) - 1;
        } else {
            if ($position < 0) {
                throw new InvalidArgumentException('position must be >= 0.');
            }

            array_splice($links, $position, 0, [$normalized]);
            $index = $position;
        }

        $this->store->saveLinks($links);

        return ['links' => $links, 'index' => $index, 'link' => $normalized];
    }

    public function update(int $index, array $changes): array
    {
        $links = $this->all();

        if (! isset($links[$index])) {
            throw new InvalidArgumentException("Link not found at index {$index}.");
        }

        $updated = $this->normalizeLink(array_merge($links[$index], $changes));
        $links[$index] = $updated;
        $this->store->saveLinks($links);

        return ['links' => $links, 'index' => $index, 'link' => $updated];
    }

    public function delete(int $index): array
    {
        $links = $this->all();

        if (! isset($links[$index])) {
            throw new InvalidArgumentException("Link not found at index {$index}.");
        }

        $deleted = $links[$index];
        unset($links[$index]);
        $links = array_values($links);

        $this->store->saveLinks($links);

        return ['links' => $links, 'deleted_link' => $deleted];
    }

    /**
     * @param array<int, int> $newOrder
     */
    public function reorder(array $newOrder): array
    {
        $links = $this->all();
        $count = count($links);

        if ($count !== count($newOrder)) {
            throw new InvalidArgumentException('new_order length must match links count.');
        }

        if ($count === 0) {
            return ['links' => []];
        }

        $max = max($newOrder);
        $min = min($newOrder);

        if ($min < 0 || $max >= $count) {
            throw new InvalidArgumentException('new_order contains out-of-range indices.');
        }

        if (count(array_unique($newOrder)) !== $count) {
            throw new InvalidArgumentException('new_order must contain unique indices.');
        }

        $reordered = [];
        foreach ($newOrder as $oldIndex) {
            $reordered[] = $links[$oldIndex];
        }

        $this->store->saveLinks($reordered);

        return ['links' => $reordered];
    }

    public function move(int $fromIndex, int $toIndex): array
    {
        $links = $this->all();
        $count = count($links);

        if (! isset($links[$fromIndex])) {
            throw new InvalidArgumentException("Link not found at index {$fromIndex}.");
        }

        if ($toIndex < 0 || $toIndex >= $count) {
            throw new InvalidArgumentException('to_index is out of range.');
        }

        $link = $links[$fromIndex];
        unset($links[$fromIndex]);
        $links = array_values($links);
        array_splice($links, $toIndex, 0, [$link]);

        $this->store->saveLinks($links);

        return ['links' => $links];
    }

    public function findIndexByUrl(string $url): ?int
    {
        foreach ($this->all() as $index => $link) {
            if (($link['url'] ?? null) === $url) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $link
     * @return array<string, mixed>
     */
    public function normalizeLink(array $link): array
    {
        $url = trim((string) ($link['url'] ?? ''));
        $label = trim((string) ($link['label'] ?? ''));
        $targetBlank = (bool) ($link['target_blank'] ?? false);

        if ($url === '') {
            throw new InvalidArgumentException('url is required.');
        }

        if ($label === '') {
            throw new InvalidArgumentException('label is required.');
        }

        if (! $this->isValidUrl($url)) {
            throw new InvalidArgumentException(
                'url must be an internal path ("/...") or a valid absolute URL (https://...).'
            );
        }

        return [
            'url' => $url,
            'label' => $label,
            'target_blank' => $targetBlank,
        ];
    }

    private function isValidUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return true;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

