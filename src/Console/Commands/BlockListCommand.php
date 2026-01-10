<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;

class BlockListCommand extends Command
{
    /**
     * @var BlockRegistry
     */
    protected BlockRegistry $registry;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:block:list
                            {--core : Afficher uniquement les blocs Core}
                            {--custom : Afficher uniquement les blocs Custom}
                            {--disabled : Afficher uniquement les blocs désactivés}
                            {--group= : Filtrer par groupe}
                            {--json : Sortie JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Liste tous les blocs disponibles';

    /**
     * Execute the console command.
     */
    public function handle(BlockRegistry $registry): int
    {
        $this->registry = $registry;
        $allBlocks = $registry->all();
        $disabledBlocks = config('page-content-manager.disabled_blocks', []);

        // Filtrer selon les options
        $filtered = $this->filterBlocks($allBlocks, $disabledBlocks);

        if ($this->option('json')) {
            return $this->outputJson($filtered, $disabledBlocks);
        }

        return $this->outputTable($filtered, $disabledBlocks);
    }

    /**
     * Filtre les blocs selon les options.
     *
     * @param array $allBlocks
     * @param array $disabledBlocks
     * @return array
     */
    protected function filterBlocks(array $allBlocks, array $disabledBlocks): array
    {
        $filtered = [];

        foreach ($allBlocks as $type => $blockClass) {
            $info = BlockCommandHelper::getBlockInfo(
                $this->getRegistry(),
                $type
            );

            if (!$info) {
                continue;
            }

            // Filtre --core
            if ($this->option('core') && $info['source'] !== 'core') {
                continue;
            }

            // Filtre --custom
            if ($this->option('custom') && $info['source'] !== 'custom') {
                continue;
            }

            // Filtre --disabled
            if ($this->option('disabled') && $info['status'] !== 'disabled') {
                continue;
            }

            // Filtre --group
            if ($this->option('group')) {
                $group = $this->option('group');
                if ($info['group'] !== $group) {
                    continue;
                }
            }

            $filtered[$type] = $info;
        }

        // Trier par ordre puis par type
        uasort($filtered, function ($a, $b) {
            if ($a['order'] !== $b['order']) {
                return $a['order'] <=> $b['order'];
            }
            return strcmp($a['type'], $b['type']);
        });

        return $filtered;
    }

    /**
     * Récupère le registry.
     *
     * @return BlockRegistry
     */
    protected function getRegistry(): BlockRegistry
    {
        return $this->registry;
    }

    /**
     * Affiche la liste sous forme de tableau.
     *
     * @param array $blocks
     * @param array $disabledBlocks
     * @return int
     */
    protected function outputTable(array $blocks, array $disabledBlocks): int
    {
        if (empty($blocks)) {
            $this->warn('⚠️  Aucun bloc trouvé.');
            
            // Afficher des suggestions selon les filtres
            $filters = [];
            if ($this->option('core')) $filters[] = 'Core';
            if ($this->option('custom')) $filters[] = 'Custom';
            if ($this->option('disabled')) $filters[] = 'désactivés';
            if ($this->option('group')) $filters[] = "groupe '{$this->option('group')}'";
            
            if (!empty($filters)) {
                $this->comment('💡 Essayez sans les filtres : ' . implode(', ', $filters));
            }
            
            return Command::SUCCESS;
        }

        // Grouper par source
        $core = [];
        $custom = [];
        $disabled = [];

        foreach ($blocks as $type => $info) {
            if ($info['status'] === 'disabled') {
                $disabled[$type] = $info;
            } elseif ($info['source'] === 'core') {
                $core[$type] = $info;
            } else {
                $custom[$type] = $info;
            }
        }

        // Afficher les blocs Core
        if (!empty($core)) {
            $this->info("Core (" . count($core) . " blocs)");
            $this->displayBlockTable($core);
            $this->newLine();
        }

        // Afficher les blocs Custom
        if (!empty($custom)) {
            $this->info("Custom (" . count($custom) . " blocs)");
            $this->displayBlockTable($custom);
            $this->newLine();
        }

        // Afficher les blocs désactivés
        if (!empty($disabled)) {
            $this->warn("Désactivés (" . count($disabled) . " bloc" . (count($disabled) > 1 ? 's' : '') . ")");
            $this->displayBlockTable($disabled);
        }

        return Command::SUCCESS;
    }

    /**
     * Affiche un tableau de blocs.
     *
     * @param array $blocks
     * @return void
     */
    protected function displayBlockTable(array $blocks): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Type', 'Classe', 'Groupe', 'Ordre', 'Statut']);

        $rows = [];
        foreach ($blocks as $info) {
            $status = $info['status'] === 'active' ? '✅ Actif' : '❌ Désactivé';
            $rows[] = [
                $info['type'],
                $info['class'],
                $info['group'] ?? '-',
                (string) $info['order'],
                $status,
            ];
        }

        $table->setRows($rows);
        $table->render();
    }

    /**
     * Affiche la sortie JSON.
     *
     * @param array $blocks
     * @param array $disabledBlocks
     * @return int
     */
    protected function outputJson(array $blocks, array $disabledBlocks): int
    {
        $data = [
            'blocks' => array_values(array_map(function ($info) {
                return [
                    'type' => $info['type'],
                    'class' => $info['class'],
                    'namespace' => $info['namespace'],
                    'source' => $info['source'],
                    'group' => $info['group'],
                    'order' => $info['order'],
                    'status' => $info['status'],
                ];
            }, $blocks)),
            'total' => count($blocks),
        ];

        $response = BlockCommandHelper::jsonResponse(true, $data);
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}

