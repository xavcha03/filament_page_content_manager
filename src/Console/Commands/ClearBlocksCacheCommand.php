<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Xavcha\PageContentManager\Blocks\BlockRegistry;

class ClearBlocksCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:blocks:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalide le cache des blocs découverts';

    /**
     * Execute the console command.
     */
    public function handle(BlockRegistry $registry): int
    {
        $registry->clearCache();

        $this->info('✅ Cache des blocs invalidé avec succès !');
        $this->comment('Les blocs seront redécouverts lors de la prochaine requête.');

        return Command::SUCCESS;
    }
}




