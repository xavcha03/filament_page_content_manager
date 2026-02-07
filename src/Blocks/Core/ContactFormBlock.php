<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class ContactFormBlock implements BlockInterface
{
    use HasMcpMetadata;

    public static function getType(): string
    {
        return 'contact_form';
    }

    public static function make(): Block
    {
        return Block::make('contact_form')
            ->label('Formulaire de contact')
            ->icon('heroicon-o-envelope')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre')
                    ->required()
                    ->maxLength(200)
                    ->default('Contactez-nous')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                TextInput::make('success_message')
                    ->label('Message de confirmation')
                    ->maxLength(500)
                    ->default('Merci pour votre message. Nous vous répondrons dans les plus brefs délais.')
                    ->columnSpanFull(),
            ]);
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'contact_form',
            'titre' => $data['titre'] ?? '',
            'description' => $data['description'] ?? '',
            'success_message' => $data['success_message'] ?? '',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'titre',
                'label' => 'Titre',
                'type' => 'string',
                'required' => true,
                'max_length' => 200,
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'string',
                'required' => false,
                'max_length' => 500,
            ],
            [
                'name' => 'success_message',
                'label' => 'Message de confirmation',
                'type' => 'string',
                'required' => false,
                'max_length' => 500,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Contactez-nous',
            'description' => 'Expliquez votre besoin et nous reviendrons vers vous.',
            'success_message' => 'Merci pour votre message. Nous revenons vers vous rapidement.',
        ];
    }
}





