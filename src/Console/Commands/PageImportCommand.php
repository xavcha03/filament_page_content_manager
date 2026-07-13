<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Xavcha\PageContentManager\Console\ExitCodes;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;
use Xavcha\PageContentManager\Services\Transfer\PageTransferService;

class PageImportCommand extends Command
{
    protected $signature = 'page-content-manager:page:import
                            {file : Chemin vers l\'archive .xavcha-page.zip}
                            {--mode=replace : replace ou skip si le slug existe}
                            {--draft : Importer en brouillon}
                            {--keep-status : Conserver le statut de publication du package}
                            {--dry-run : Prévisualiser sans importer}
                            {--json : Sortie JSON}';

    protected $description = 'Importe une archive .xavcha-page.zip';

    public function handle(PageTransferService $transferService): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("Fichier introuvable : {$file}");

            return ExitCodes::INVALID_INPUT;
        }

        try {
            if ($this->option('dry-run')) {
                $preview = $transferService->previewFromPath($file);

                if ($this->option('json')) {
                    $this->line(json_encode(
                        BlockCommandHelper::jsonResponse(true, $preview, $preview['errors'], $preview['warnings']),
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                    ));

                    return $preview['valid'] ? ExitCodes::SUCCESS : ExitCodes::VALIDATION_ERROR;
                }

                $this->renderPreview($preview);

                return $preview['valid'] ? ExitCodes::SUCCESS : ExitCodes::VALIDATION_ERROR;
            }

            $result = $transferService->importFromPath($file, [
                'on_conflict' => $this->option('mode') === 'skip' ? 'skip' : 'replace',
                'import_as_draft' => $this->resolveImportAsDraft(),
            ]);

            if ($this->option('json')) {
                $this->line(json_encode(
                    BlockCommandHelper::jsonResponse(
                        true,
                        [
                            'preview' => $result['preview'],
                            'imported' => collect($result['pages'])->map(fn ($page) => [
                                'id' => $page->id,
                                'slug' => $page->slug,
                                'title' => $page->title,
                            ])->all(),
                        ],
                        [],
                        $result['preview']['warnings'],
                        count($result['pages']) . ' page(s) importée(s).',
                    ),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                ));

                return ExitCodes::SUCCESS;
            }

            $this->info(count($result['pages']) . ' page(s) importée(s).');

            foreach ($result['pages'] as $page) {
                $this->line("- {$page->title} ({$page->slug})");
            }

            return ExitCodes::SUCCESS;
        } catch (\Throwable $exception) {
            if ($this->option('json')) {
                $this->line(json_encode(
                    BlockCommandHelper::jsonResponse(false, [], [$exception->getMessage()], []),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                ));
            } else {
                $this->error($exception->getMessage());
            }

            return ExitCodes::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    protected function renderPreview(array $preview): void
    {
        $this->info('Prévisualisation de l\'import');
        $this->newLine();

        foreach ($preview['pages'] as $page) {
            $action = $page['action'] === 'replace' ? 'Remplacement' : 'Création';
            $this->line("• [{$action}] {$page['title']} ({$page['slug']}) — {$page['block_count']} bloc(s)");

            if ($page['existing_title'] ?? null) {
                $this->line("  Page existante : {$page['existing_title']}");
            }
        }

        $media = $preview['media'];
        $this->newLine();
        $this->line("Médias : {$media['to_import']} à importer, {$media['existing']} déjà présents.");

        if ($preview['warnings'] !== []) {
            $this->newLine();
            $this->warn('Avertissements :');

            foreach ($preview['warnings'] as $warning) {
                $this->line("- {$warning}");
            }
        }

        if ($preview['errors'] !== []) {
            $this->newLine();
            $this->error('Erreurs :');

            foreach ($preview['errors'] as $error) {
                $this->line("- {$error}");
            }
        }
    }

    protected function resolveImportAsDraft(): bool
    {
        if ($this->option('keep-status')) {
            return false;
        }

        if ($this->option('draft')) {
            return true;
        }

        return (bool) config('page-content-manager.transfer.import_force_draft_default', true);
    }
}
