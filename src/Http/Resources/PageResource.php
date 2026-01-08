<?php

namespace Xavcha\PageContentManager\Http\Resources;

use Xavcha\PageContentManager\Blocks\SectionTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'sections' => $transformerService->transform($this->getSections()),
            'metadata' => $this->getMetadata(),
        ];
    }
}

