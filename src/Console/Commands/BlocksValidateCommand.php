<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Console\ExitCodes;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;

class BlocksValidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:blocks:validate {--json : Sortie JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Valide tous les blocs';

    /**
     * Execute the console command.
     */
    public function handle(BlockRegistry $registry): int
    {
        $allBlocks = $registry->all();

        if (empty($allBlocks)) {
            if ($this->option('json')) {
                $response = BlockCommandHelper::jsonResponse(
                    true,
                    ['valid' => 0, 'warnings' => 0, 'errors' => 0, 'results' => []],
                    [],
                    [],
                    'Aucun bloc à valider'
                );
                $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return Command::SUCCESS;
            }

            $this->warn('Aucun bloc à valider.');
            return Command::SUCCESS;
        }

        $results = [];
        $validCount = 0;
        $warningCount = 0;
        $errorCount = 0;

        $this->info('🔍 Validation des blocs en cours...');
        $this->newLine();

        $total = count($allBlocks);
        $current = 0;

        // Barre de progression si mode interactif et plus de 3 blocs
        $useProgressBar = !$this->option('json') && $total > 3;
        $progressBar = null;
        
        if ($useProgressBar && method_exists($this, 'withProgressBar')) {
            $progressBar = $this->output->createProgressBar($total);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
            $progressBar->setMessage('Démarrage...');
            $progressBar->start();
        }

        foreach ($allBlocks as $type => $blockClass) {
            $current++;
            
            if ($progressBar) {
                $progressBar->setMessage("Validation de {$type}...");
            }

            $validation = BlockCommandHelper::validateBlock($type, $blockClass);

            $status = 'valid';
            if (!empty($validation['errors'])) {
                $status = 'error';
                $errorCount++;
            } elseif (!empty($validation['warnings'])) {
                $status = 'warning';
                $warningCount++;
            } else {
                $validCount++;
            }

            $results[] = [
                'type' => $type,
                'status' => $status,
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
            ];

            // Afficher le résultat en temps réel (mode interactif uniquement, sans barre de progression)
            if (!$this->option('json') && !$progressBar) {
                $icon = match ($status) {
                    'error' => '❌',
                    'warning' => '⚠️ ',
                    default => '✅',
                };

                $this->line("{$icon} {$type}" . (!empty($validation['errors']) ? ' - Erreur: ' . implode(', ', $validation['errors']) : ''));
                if (!empty($validation['warnings'])) {
                    $this->comment("   Avertissement: " . implode(', ', $validation['warnings']));
                }
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->setMessage('Terminé !');
            $progressBar->finish();
            $this->newLine(2);
        }

        // Résumé
        $this->newLine();
        $this->info('Résumé:');
        $this->line("  - {$validCount} bloc" . ($validCount > 1 ? 's' : '') . " valide" . ($validCount > 1 ? 's' : ''));
        if ($warningCount > 0) {
            $this->warn("  - {$warningCount} bloc" . ($warningCount > 1 ? 's' : '') . " avec avertissement" . ($warningCount > 1 ? 's' : ''));
        }
        if ($errorCount > 0) {
            $this->error("  - {$errorCount} bloc" . ($errorCount > 1 ? 's' : '') . " avec erreur" . ($errorCount > 1 ? 's' : ''));
        }

        if ($this->option('json')) {
            $response = BlockCommandHelper::jsonResponse(
                $errorCount === 0,
                [
                    'valid' => $validCount,
                    'warnings' => $warningCount,
                    'errors' => $errorCount,
                    'results' => $results,
                ],
                $errorCount > 0 ? ['Certains blocs ont des erreurs'] : [],
                $warningCount > 0 ? ['Certains blocs ont des avertissements'] : []
            );
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $errorCount > 0 ? ExitCodes::VALIDATION_ERROR : Command::SUCCESS;
    }
}

