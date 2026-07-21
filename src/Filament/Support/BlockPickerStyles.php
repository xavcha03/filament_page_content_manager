<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Filament\Support;

class BlockPickerStyles
{
    public static function path(): string
    {
        return dirname(__DIR__, 3) . '/resources/css/block-picker.css';
    }

    public static function inline(): string
    {
        $path = self::path();

        if (! is_file($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }
}
