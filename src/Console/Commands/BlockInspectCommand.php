<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Console\ExitCodes;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;

class BlockInspectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:block:inspect
                            {type : Le type du bloc à inspecter}
                            {--json : Sortie JSON}
                            {--detailed : Plus de détails}
                            {--show-schema : Afficher le schéma complet}
                            {--show-transform : Afficher la méthode transform()}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspecte un bloc en détail';

    /**
     * Execute the console command.
     */
    public function handle(BlockRegistry $registry): int
    {
        $type = $this->argument('type');
        $blockClass = $registry->get($type);

        if (!$blockClass) {
            // Chercher des blocs similaires pour suggestions
            $similar = BlockCommandHelper::findSimilarBlocks($registry, $type, 3);
            
            if ($this->option('json')) {
                $response = BlockCommandHelper::jsonResponse(
                    false,
                    null,
                    ["Le bloc '{$type}' n'existe pas"],
                    !empty($similar) ? ['Blocs similaires disponibles: ' . implode(', ', array_column($similar, 'type'))] : [],
                    "Bloc non trouvé"
                );
                $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return ExitCodes::BLOCK_NOT_FOUND;
            }

            $this->error("Le bloc '{$type}' n'existe pas.");
            
            // Afficher des suggestions si disponibles
            if (!empty($similar)) {
                $this->newLine();
                $this->comment('💡 Blocs similaires disponibles :');
                foreach ($similar as $suggestion) {
                    $this->line("  - {$suggestion['type']}");
                }
                $this->newLine();
            }
            
            return ExitCodes::BLOCK_NOT_FOUND;
        }

        $info = BlockCommandHelper::getBlockInfo($registry, $type);

        if (!$info) {
            $this->error("Impossible de récupérer les informations du bloc '{$type}'.");
            return Command::FAILURE;
        }

        if ($this->option('json')) {
            return $this->outputJson($info, $blockClass);
        }

        return $this->outputDetails($info, $blockClass);
    }

    /**
     * Affiche les détails du bloc.
     *
     * @param array $info
     * @param string $blockClass
     * @return int
     */
    protected function outputDetails(array $info, string $blockClass): int
    {
        $this->info("🔍 Bloc: {$info['type']}");
        $this->newLine();

        // Tableau principal
        $table = new Table($this->output);
        $table->setHeaders(['Propriété', 'Valeur']);

        $rows = [
            ['Classe', $info['class']],
            ['Namespace', $info['namespace']],
            ['Type', $info['type']],
            ['Ordre', (string) $info['order']],
            ['Groupe', $info['group'] ?? '-'],
            ['Source', $info['source'] === 'core' ? 'Core' : 'Custom'],
            ['Statut', $info['status'] === 'active' ? '✅ Actif' : '❌ Désactivé'],
        ];

        $table->setRows($rows);
        $table->render();

        // Champs du formulaire
        if (!empty($info['fields'])) {
            $this->newLine();
            $this->comment('Champs du formulaire:');

            $fieldsTable = new Table($this->output);
            $fieldsTable->setHeaders(['Nom', 'Type', 'Requis']);

            $fieldsRows = [];
            foreach ($info['fields'] as $field) {
                $fieldsRows[] = [
                    $field['name'],
                    $field['type'],
                    isset($field['required']) && $field['required'] ? 'Oui' : 'Non',
                ];
            }

            $fieldsTable->setRows($fieldsRows);
            $fieldsTable->render();
        }

        // Validation
        $this->newLine();
        $this->comment('Validation:');
        $this->line('  Transformation: ' . ($info['has_transform'] ? '✅ Implémentée' : '❌ Manquante'));
        $this->line('  Validation: ' . ($info['has_validation'] ? '✅ Implémentée' : '⚠️  Optionnelle'));

        // Options supplémentaires
        if ($this->option('show-schema')) {
            $this->newLine();
            $this->comment('Schéma complet:');
            try {
                $block = $blockClass::make();
                $this->line(json_encode($block->getSchema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                $this->error("Erreur lors de la récupération du schéma: {$e->getMessage()}");
            }
        }

        if ($this->option('show-transform')) {
            $this->newLine();
            $this->comment('Méthode transform():');
            try {
                $reflection = new \ReflectionClass($blockClass);
                $method = $reflection->getMethod('transform');
                $this->line($this->getMethodSource($method));
            } catch (\ReflectionException $e) {
                $this->error("Erreur lors de la récupération de la méthode: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Affiche la sortie JSON.
     *
     * @param array $info
     * @param string $blockClass
     * @return int
     */
    protected function outputJson(array $info, string $blockClass): int
    {
        $data = [
            'type' => $info['type'],
            'class' => $info['class'],
            'namespace' => $info['namespace'],
            'order' => $info['order'],
            'group' => $info['group'],
            'source' => $info['source'],
            'status' => $info['status'],
            'fields' => $info['fields'],
            'has_transform' => $info['has_transform'],
            'has_validation' => $info['has_validation'],
        ];

        // Ajouter des détails supplémentaires si detailed
        if ($this->option('detailed')) {
            try {
                $block = $blockClass::make();
                $data['schema'] = $block->getSchema();
            } catch (\Throwable $e) {
                $data['schema_error'] = $e->getMessage();
            }
        }

        $response = BlockCommandHelper::jsonResponse(true, $data);
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }

    /**
     * Récupère le code source d'une méthode.
     *
     * @param \ReflectionMethod $method
     * @return string
     */
    protected function getMethodSource(\ReflectionMethod $method): string
    {
        $filename = $method->getFileName();
        
        if (!$filename || !file_exists($filename)) {
            return '// Impossible de récupérer le code source';
        }
        
        try {
            $start = $method->getStartLine() - 1;
            $end = $method->getEndLine();
            $fileLines = file($filename);
            
            if ($fileLines === false) {
                return '// Impossible de lire le fichier';
            }
            
            $lines = array_slice($fileLines, $start, $end - $start);
            return implode('', $lines);
        } catch (\Throwable $e) {
            return '// Erreur lors de la récupération du code source: ' . $e->getMessage();
        }
    }
}

