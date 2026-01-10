<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;

class BlocksStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:blocks:stats {--json : Sortie JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Affiche les statistiques des blocs';

    /**
     * Execute the console command.
     */
    public function handle(BlockRegistry $registry): int
    {
        $stats = BlockCommandHelper::getStats($registry);

        if ($this->option('json')) {
            return $this->outputJson($stats);
        }

        return $this->outputTable($stats);
    }

    /**
     * Affiche les statistiques sous forme de tableau.
     *
     * @param array $stats
     * @return int
     */
    protected function outputTable(array $stats): int
    {
        $this->info('📊 Statistiques des Blocs');
        $this->newLine();

        $table = new Table($this->output);
        $table->setHeaders(['Statistique', 'Valeur']);

        $rows = [
            ['Total', (string) $stats['total']],
            ['Core', (string) $stats['core']],
            ['Custom', (string) $stats['custom']],
            ['Actifs', (string) $stats['active']],
            ['Désactivés', (string) $stats['disabled']],
        ];

        $table->setRows($rows);
        $table->render();

        // Statistiques par groupe
        if (!empty($stats['by_group'])) {
            $this->newLine();
            $this->comment('Par groupe:');

            $groupTable = new Table($this->output);
            $groupTable->setHeaders(['Groupe', 'Nombre']);

            $groupRows = [];
            foreach ($stats['by_group'] as $group => $count) {
                $groupRows[] = [$group, (string) $count];
            }

            $groupTable->setRows($groupRows);
            $groupTable->render();
        }

        // Utilisation dans les pages
        if (!empty($stats['usage'])) {
            $this->newLine();
            $this->comment('Utilisation dans les pages:');

            $usageTable = new Table($this->output);
            $usageTable->setHeaders(['Bloc', 'Pages']);

            $usageRows = [];
            arsort($stats['usage']);
            foreach ($stats['usage'] as $type => $count) {
                $usageRows[] = [$type, (string) $count];
            }

            $usageTable->setRows($usageRows);
            $usageTable->render();
        }

        return Command::SUCCESS;
    }

    /**
     * Affiche la sortie JSON.
     *
     * @param array $stats
     * @return int
     */
    protected function outputJson(array $stats): int
    {
        $response = BlockCommandHelper::jsonResponse(true, $stats);
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}

