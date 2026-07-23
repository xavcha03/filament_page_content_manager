<?php

namespace Xavcha\PageContentManager\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Xavcha\PageContentManager\Blocks\SectionTransformer;
use Xavcha\PageContentManager\Experiences\ExperienceDataResolver;
use Xavcha\PageContentManager\Models\Page;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $transformerService = app(SectionTransformer::class);
        $experienceResolver = app(ExperienceDataResolver::class);

        $contentMode = $this->content_mode ?? Page::CONTENT_MODE_BLOCKS;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'content_mode' => $contentMode,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'robots' => $this->seo_noindex ? 'noindex' : null,
            'sections' => $transformerService->transform($this->getSections()),
            'metadata' => $this->getMetadata(),
            'experience' => $experienceResolver->resolveForPage($this->resource),
        ];
    }
}
