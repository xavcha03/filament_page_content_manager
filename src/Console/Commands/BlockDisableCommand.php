<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Console\ExitCodes;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;

class BlockDisableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:block:disable
                            {type : Le type du bloc à désactiver}
                            {--force : Pas de confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Désactive un bloc';

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

        $disabledBlocks = config('page-content-manager.disabled_blocks', []);

        if (in_array($type, $disabledBlocks, true)) {
            $this->warn("Le bloc '{$type}' est déjà désactivé.");
            return Command::SUCCESS;
        }

        $isNonInteractive = BlockCommandHelper::isNonInteractive($this);

        if (!$isNonInteractive) {
            $this->warn("⚠️  Attention : Le bloc '{$type}' sera désactivé.");

            if (class_exists(\Laravel\Prompts\Prompt::class)) {
                $confirmed = \Laravel\Prompts\confirm(
                    label: 'Êtes-vous sûr ?',
                    default: false
                );
            } else {
                $confirmed = $this->confirm('Êtes-vous sûr ?', false);
            }

            if (!$confirmed) {
                $this->info('Opération annulée.');
                return Command::SUCCESS;
            }
        }

        // Ajouter à la liste des blocs désactivés
        $disabledBlocks[] = $type;
        $disabledBlocks = array_unique($disabledBlocks);
        sort($disabledBlocks);

        try {
            if ($this->updateConfig($disabledBlocks)) {
                $this->info("✅ Bloc '{$type}' désactivé avec succès !");
                $this->comment("📝 Ajouté à la liste des blocs désactivés dans config.");
                $this->comment("💡 Le bloc ne sera plus disponible dans le Builder Filament.");
                return Command::SUCCESS;
            }

            $this->error("❌ Erreur lors de la mise à jour de la configuration.");
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error("❌ Erreur lors de la désactivation du bloc : {$e->getMessage()}");
            $this->comment("Vérifiez les permissions du fichier de configuration.");
            return Command::FAILURE;
        }
    }

    /**
     * Met à jour le fichier de configuration.
     *
     * @param array $disabledBlocks
     * @return bool
     */
    protected function updateConfig(array $disabledBlocks): bool
    {
        $configPath = config_path('page-content-manager.php');

        // Si le fichier n'existe pas, utiliser celui du package
        if (!File::exists($configPath)) {
            $configPath = __DIR__ . '/../../../config/page-content-manager.php';
        }

        if (!File::exists($configPath)) {
            $this->error("Le fichier de configuration n'existe pas.");
            return false;
        }

        $content = File::get($configPath);

        // Trouver et remplacer le tableau disabled_blocks
        $pattern = "/('disabled_blocks'\s*=>\s*)\[[^\]]*\]/";
        $replacement = "'disabled_blocks' => " . $this->formatArray($disabledBlocks);

        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent === null) {
            $this->error("Erreur lors de la modification du fichier de configuration.");
            return false;
        }

        File::put($configPath, $newContent);

        // Nettoyer le cache de configuration
        Artisan::call('config:clear');

        return true;
    }

    /**
     * Formate un tableau pour l'écriture dans un fichier PHP.
     *
     * @param array $array
     * @return string
     */
    protected function formatArray(array $array): string
    {
        if (empty($array)) {
            return '[]';
        }

        $items = array_map(function ($item) {
            return "'{$item}'";
        }, $array);

        return "[\n        " . implode(",\n        ", $items) . ",\n    ]";
    }
}

