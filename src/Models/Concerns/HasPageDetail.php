<?php

namespace Xavcha\PageContentManager\Models\Concerns;

trait HasPageDetail
{
    use HasContentBlocks;

    /**
     * Boot le trait.
     *
     * @return void
     */
    public static function bootHasPageDetail(): void
    {
        static::saving(function ($model) {
            if (method_exists($model, 'normalizeContent')) {
                $model->normalizeContent();
            }
        });
    }

}

