<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Xavcha\PageContentManager\Console\ExitCodes;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\Transfer\PageTransferService;

class PageExportCommand extends Command
{
    protected $signature = 'page-content-manager:page:export
                            {slug? : Slug de la page à exporter}
                            {--id= : ID de la page à exporter}
                            {--output= : Chemin de sortie de l\'archive}
                            {--pages=* : Slugs supplémentaires pour un export multiple}';

    protected $description = 'Exporte une ou plusieurs pages au format .xavcha-page.zip';

    public function handle(PageTransferService $transferService): int
    {
        $pages = $this->resolvePages();

        if ($pages === []) {
            $this->error('Aucune page trouvée pour l\'export.');

            return ExitCodes::INVALID_INPUT;
        }

        try {
            $output = $transferService->exportToFile($pages, $this->option('output') ?: null);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return ExitCodes::FAILURE;
        }

        $this->info('Archive exportée : ' . $output);

        return ExitCodes::SUCCESS;
    }

    /**
     * @return list<Page>
     */
    protected function resolvePages(): array
    {
        $slugs = array_filter([
            $this->argument('slug'),
            ...$this->option('pages'),
        ]);

        if ($this->option('id')) {
            $page = Page::query()->find($this->option('id'));

            return $page ? [$page] : [];
        }

        if ($slugs === []) {
            return [];
        }

        $pages = [];

        foreach ($slugs as $slug) {
            $page = Page::query()->where('slug', $slug)->first();

            if ($page !== null) {
                $pages[] = $page;
            }
        }

        return $pages;
    }
}
