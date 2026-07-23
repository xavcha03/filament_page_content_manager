<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Tests\Fixtures;

use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Experiences\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Experiences\Contracts\ExperienceInterface;

class DemoExperience implements ExperienceInterface
{
    use HasMcpMetadata;

    public static function getKey(): string
    {
        return 'demo-experience';
    }

    public static function getLabel(): string
    {
        return 'Demo Experience';
    }

    public static function make(): array
    {
        return [
            TextInput::make('hero_title')
                ->label('Titre')
                ->required(),
        ];
    }

    public static function transform(array $data): array
    {
        return [
            'hero_title' => $data['hero_title'] ?? '',
        ];
    }

    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'hero_title',
                'label' => 'Titre',
                'type' => 'string',
                'required' => true,
            ],
        ];
    }

    public static function getMcpExample(): array
    {
        return ['hero_title' => 'Hello'];
    }
}
