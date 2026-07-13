<?php

namespace Xavcha\PageContentManager\Filament\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Xavcha\PageContentManager\Services\Transfer\PageTransferService;

class PageImportPreviewForm
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function schema(): array
    {
        $maxSizeMb = (int) config('page-content-manager.transfer.max_upload_size_mb', 50);

        return [
            Forms\Components\FileUpload::make('archive')
                ->label('Archive')
                ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                ->directory('page-imports')
                ->required()
                ->maxSize($maxSizeMb * 1024)
                ->live()
                ->afterStateUpdated(function (): void {
                    // Force le rafraîchissement du récapitulatif.
                }),
            Forms\Components\Placeholder::make('preview_summary')
                ->label('Récapitulatif')
                ->content(fn (Get $get): HtmlString | string => self::buildPreviewHtml($get('archive')))
                ->visible(fn (Get $get): bool => filled($get('archive'))),
            Forms\Components\Radio::make('on_conflict')
                ->label('Si le slug existe déjà')
                ->options([
                    'replace' => 'Remplacer la page existante (mise à jour complète)',
                    'skip' => 'Ignorer les pages déjà existantes',
                ])
                ->default('replace')
                ->required()
                ->visible(fn (Get $get): bool => filled($get('archive'))),
            Forms\Components\Checkbox::make('import_as_draft')
                ->label('Importer en brouillon')
                ->default((bool) config('page-content-manager.transfer.import_force_draft_default', true))
                ->visible(fn (Get $get): bool => filled($get('archive'))),
        ];
    }

    protected static function buildPreviewHtml(mixed $archive): HtmlString | string
    {
        if (! filled($archive)) {
            return 'Sélectionnez une archive pour afficher le récapitulatif.';
        }

        $path = self::resolveArchivePath($archive);

        if ($path === null || ! is_file($path)) {
            return 'Archive introuvable ou encore en cours de téléversement.';
        }

        try {
            $preview = app(PageTransferService::class)->previewFromPath($path);
        } catch (\Throwable $exception) {
            return 'Impossible de lire l\'archive : ' . $exception->getMessage();
        }

        $lines = [];

        if (isset($preview['pages']) && is_array($preview['pages'])) {
            foreach ($preview['pages'] as $page) {
                $action = ($page['action'] ?? 'create') === 'replace' ? 'Remplacement' : 'Nouvelle page';
                $line = "<strong>[{$action}]</strong> " . e((string) ($page['title'] ?? '')) . ' (' . e((string) ($page['slug'] ?? '')) . ')';

                if (($page['type'] ?? '') === 'home') {
                    $line .= ' — <em>Page Home</em>';
                }

                if (! empty($page['existing_title'])) {
                    $line .= '<br><span style="margin-left:1rem;color:#b45309;">Page existante : ' . e((string) $page['existing_title']) . '</span>';
                } elseif (($page['action'] ?? 'create') === 'create') {
                    $line .= '<br><span style="margin-left:1rem;color:#0369a1;">Une nouvelle page sera créée.</span>';
                }

                $line .= '<br><span style="margin-left:1rem;">' . (int) ($page['block_count'] ?? 0) . ' bloc(s)</span>';
                $lines[] = $line;
            }
        }

        $media = $preview['media'] ?? ['to_import' => 0, 'existing' => 0, 'missing' => []];
        $lines[] = '<br><strong>Médias</strong> : ' . (int) ($media['to_import'] ?? 0) . ' à importer, ' . (int) ($media['existing'] ?? 0) . ' déjà présents.';

        if (! empty($media['missing'])) {
            $lines[] = '<span style="color:#b45309;">' . count($media['missing']) . ' fichier(s) média manquant(s) dans l\'archive.</span>';
        }

        if (! empty($preview['warnings'])) {
            $lines[] = '<br><strong>Avertissements</strong><ul style="margin:0.5rem 0 0 1rem;">';

            foreach ($preview['warnings'] as $warning) {
                $lines[] = '<li>' . e((string) $warning) . '</li>';
            }

            $lines[] = '</ul>';
        }

        if (! empty($preview['errors'])) {
            $lines[] = '<br><strong style="color:#b91c1c;">Erreurs</strong><ul style="margin:0.5rem 0 0 1rem;color:#b91c1c;">';

            foreach ($preview['errors'] as $error) {
                $lines[] = '<li>' . e((string) $error) . '</li>';
            }

            $lines[] = '</ul>';
        }

        return new HtmlString(implode('<br>', $lines));
    }

    public static function resolveArchivePathForImport(mixed $archive): ?string
    {
        return self::resolveArchivePath($archive);
    }

    protected static function resolveArchivePath(mixed $archive): ?string
    {
        if (is_string($archive)) {
            return Storage::disk('local')->exists($archive)
                ? Storage::disk('local')->path($archive)
                : (is_file($archive) ? $archive : null);
        }

        if (is_array($archive)) {
            $first = reset($archive);

            return is_string($first) ? self::resolveArchivePath($first) : null;
        }

        return null;
    }
}
