<?php

namespace Xavcha\PageContentManager\Filament\Forms;

use Filament\Forms;
use Xavcha\PageContentManager\Enums\DeletedPageResponseType;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PageDeletionService;

class PageDeletionPolicyForm
{
    /**
     * @param  Page|iterable<Page>|null  $exceptPages
     * @return array<int, Forms\Components\Component>
     */
    public static function schema(Page | iterable | null $exceptPages = null): array
    {
        $defaultType = app(PageDeletionService::class)->defaultResponseType()->value;
        $exceptIds = self::resolveExceptIds($exceptPages);

        return [
            Forms\Components\Placeholder::make('deletion_notice')
                ->label('')
                ->content('Cette page ne sera plus visible sur le site. Que doit faire l\'ancienne URL ?'),
            Forms\Components\Radio::make('deleted_response_type')
                ->label('Politique pour l\'ancienne URL')
                ->options(DeletedPageResponseType::options())
                ->default($defaultType)
                ->required()
                ->live(),
            Forms\Components\Select::make('redirect_target_page_id')
                ->label('Page de destination')
                ->options(fn (): array => Page::query()
                    ->where('type', 'standard')
                    ->published()
                    ->when($exceptIds !== [], fn ($query) => $query->whereNotIn('id', $exceptIds))
                    ->orderBy('title')
                    ->pluck('title', 'id')
                    ->all())
                ->searchable()
                ->required(fn ($get): bool => $get('deleted_response_type') === DeletedPageResponseType::RedirectToPage->value)
                ->visible(fn ($get): bool => $get('deleted_response_type') === DeletedPageResponseType::RedirectToPage->value),
            Forms\Components\TextInput::make('redirect_target_url')
                ->label('URL de destination')
                ->url()
                ->maxLength(2048)
                ->required(fn ($get): bool => $get('deleted_response_type') === DeletedPageResponseType::RedirectToUrl->value)
                ->visible(fn ($get): bool => $get('deleted_response_type') === DeletedPageResponseType::RedirectToUrl->value),
        ];
    }

    /**
     * @param  Page|iterable<Page>|null  $exceptPages
     * @return list<int>
     */
    protected static function resolveExceptIds(Page | iterable | null $exceptPages): array
    {
        if ($exceptPages === null) {
            return [];
        }

        if ($exceptPages instanceof Page) {
            return [$exceptPages->id];
        }

        return collect($exceptPages)->pluck('id')->filter()->all();
    }

    public static function applyDeletion(Page $page, array $data): void
    {
        $responseType = DeletedPageResponseType::from($data['deleted_response_type']);

        app(PageDeletionService::class)->softDelete(
            $page,
            $responseType,
            isset($data['redirect_target_page_id']) ? (int) $data['redirect_target_page_id'] : null,
            $data['redirect_target_url'] ?? null,
        );
    }
}
