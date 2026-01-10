<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;

class BlockEnableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:block:enable
                            {type : Le type du bloc à activer}
                            {--force : Pas de confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Active un bloc';

    /**
     * Execute the console command.
     */
    public function handle(BlockRegistry $registry): int
    {
        $type = $this->argument('type');
        $disabledBlocks = config('page-content-manager.disabled_blocks', []);

        if (!in_array($type, $disabledBlocks, true)) {
            $this->info("Le bloc '{$type}' est déjà actif.");
            return Command::SUCCESS;
        }

        // Vérifier que le bloc existe
        $registry->clearCache(); // Forcer la redécouverte
        $blockClass = $registry->get($type);

        if (!$blockClass) {
            $this->warn("Le bloc '{$type}' n'existe pas, mais il sera retiré de la liste des blocs désactivés.");
        }

        $isNonInteractive = BlockCommandHelper::isNonInteractive($this);

        if (!$isNonInteractive) {
            $this->info("Le bloc '{$type}' sera activé.");

            if (class_exists(\Laravel\Prompts\Prompt::class)) {
                $confirmed = \Laravel\Prompts\confirm(
                    label: 'Êtes-vous sûr ?',
                    default: true
                );
            } else {
                $confirmed = $this->confirm('Êtes-vous sûr ?', true);
            }

            if (!$confirmed) {
                $this->info('Opération annulée.');
                return Command::SUCCESS;
            }
        }

        // Retirer de la liste des blocs désactivés
        $disabledBlocks = array_values(array_filter($disabledBlocks, function ($block) use ($type) {
            return $block !== $type;
        }));

        if ($this->updateConfig($disabledBlocks)) {
            $this->info("✅ Bloc '{$type}' activé avec succès !");
            $this->comment("📝 Retiré de la liste des blocs désactivés dans config.");
            return Command::SUCCESS;
        }

        $this->error("Erreur lors de la mise à jour de la configuration.");
        return Command::FAILURE;
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

