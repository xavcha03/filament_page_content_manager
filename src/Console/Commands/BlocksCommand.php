<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan as ArtisanFacade;

class BlocksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:blocks
                            {action? : L\'action à exécuter (list/create/disable/enable/inspect/stats/validate)}
                            {--type= : Le type de bloc (pour disable/enable/inspect)}
                            {--name= : Le nom du bloc (pour create)}
                            {--group= : Le groupe du bloc (pour create)}
                            {--with-media : Utiliser le trait HasMediaTransformation (pour create)}
                            {--order= : L\'ordre d\'affichage (pour create)}
                            {--force : Pas de confirmation}
                            {--json : Sortie JSON}
                            {--core : Filtrer les blocs Core (pour list)}
                            {--custom : Filtrer les blocs Custom (pour list)}
                            {--disabled : Filtrer les blocs désactivés (pour list)}
                            {--group-filter= : Filtrer par groupe (pour list)}
                            {--verbose : Plus de détails (pour inspect)}
                            {--show-schema : Afficher le schéma complet (pour inspect)}
                            {--show-transform : Afficher la méthode transform() (pour inspect)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menu interactif pour la gestion des blocs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        // Si une action est fournie, déléguer à la commande correspondante
        if ($action) {
            return $this->delegateToCommand($action);
        }

        // Sinon, afficher le menu interactif
        return $this->showMenu();
    }

    /**
     * Affiche le menu interactif.
     *
     * @return int
     */
    protected function showMenu(): int
    {
        $this->displayHeader();

        if (class_exists(\Laravel\Prompts\Prompt::class)) {
            $choice = \Laravel\Prompts\select(
                label: 'Choisissez une option',
                options: [
                    '1' => '📋 Lister tous les blocs',
                    '2' => '➕ Créer un nouveau bloc',
                    '3' => '🗑️  Désactiver un bloc',
                    '4' => '✅ Activer un bloc',
                    '5' => '🔍 Inspecter un bloc en détail',
                    '6' => '📊 Afficher les statistiques',
                    '7' => '🧪 Valider tous les blocs',
                    '8' => '🔄 Rafraîchir le cache des blocs',
                    '0' => '❌ Quitter',
                ],
                default: '1'
            );
        } else {
            $this->displayMenuOptions();
            $choice = $this->ask('Choisissez une option [0-8]', '1');
        }

        $actionMap = [
            '1' => 'list',
            '2' => 'create',
            '3' => 'disable',
            '4' => 'enable',
            '5' => 'inspect',
            '6' => 'stats',
            '7' => 'validate',
            '8' => 'clear-cache',
            '0' => null,
        ];

        $action = $actionMap[$choice] ?? null;

        if ($action === null) {
            $this->info('Au revoir !');
            return Command::SUCCESS;
        }

        if ($action === 'clear-cache') {
            return ArtisanFacade::call('page-content-manager:blocks:clear-cache');
        }

        return $this->delegateToCommand($action);
    }

    /**
     * Affiche l'en-tête du menu.
     *
     * @return void
     */
    protected function displayHeader(): void
    {
        $this->info('╔═══════════════════════════════════════════════════════╗');
        $this->info('║                                                       ║');
        $this->info('║     🎨  Gestionnaire de Blocs - Page Content Manager ║');
        $this->info('║                                                       ║');
        $this->info('╚═══════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * Affiche les options du menu (fallback pour Symfony Console).
     *
     * @return void
     */
    protected function displayMenuOptions(): void
    {
        $this->info('┌───────────────────────────────────────────────────────┐');
        $this->info('│  📋 Actions disponibles                                   │');
        $this->info('├───────────────────────────────────────────────────────┤');
        $this->info('│  1. 📋 Lister tous les blocs                        │');
        $this->info('│  2. ➕ Créer un nouveau bloc                          │');
        $this->info('│  3. 🗑️  Désactiver un bloc                           │');
        $this->info('│  4. ✅ Activer un bloc                               │');
        $this->info('│  5. 🔍 Inspecter un bloc en détail                   │');
        $this->info('│  6. 📊 Afficher les statistiques                     │');
        $this->info('│  7. 🧪 Valider tous les blocs                        │');
        $this->info('│  8. 🔄 Rafraîchir le cache des blocs                  │');
        $this->info('│  0. ❌ Quitter                                       │');
        $this->info('└───────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    /**
     * Délègue à la commande correspondante.
     *
     * @param string $action
     * @return int
     */
    protected function delegateToCommand(string $action): int
    {
        $commandMap = [
            'list' => 'page-content-manager:block:list',
            'create' => 'page-content-manager:make-block',
            'disable' => 'page-content-manager:block:disable',
            'enable' => 'page-content-manager:block:enable',
            'inspect' => 'page-content-manager:block:inspect',
            'stats' => 'page-content-manager:blocks:stats',
            'validate' => 'page-content-manager:blocks:validate',
        ];

        if (!isset($commandMap[$action])) {
            $this->error("Action inconnue: {$action}");
            return Command::FAILURE;
        }

        $command = $commandMap[$action];
        $arguments = [];
        $options = [];

        // Préparer les arguments
        switch ($action) {
            case 'create':
                if ($this->option('name')) {
                    $arguments['name'] = $this->option('name');
                }
                break;
            case 'disable':
            case 'enable':
            case 'inspect':
                if ($this->option('type')) {
                    $arguments['type'] = $this->option('type');
                } elseif ($action === 'inspect') {
                    // Demander le type si non fourni
                    $type = $this->ask('Quel bloc voulez-vous inspecter ?');
                    if ($type) {
                        $arguments['type'] = $type;
                    } else {
                        $this->error('Le type du bloc est requis.');
                        return Command::FAILURE;
                    }
                } else {
                    // Demander le type si non fourni
                    $type = $this->ask("Quel bloc voulez-vous " . ($action === 'disable' ? 'désactiver' : 'activer') . " ?");
                    if ($type) {
                        $arguments['type'] = $type;
                    } else {
                        $this->error('Le type du bloc est requis.');
                        return Command::FAILURE;
                    }
                }
                break;
        }

        // Préparer les options
        if ($this->option('force')) {
            $options['--force'] = true;
        }
        if ($this->option('json')) {
            $options['--json'] = true;
        }
        if ($this->option('core')) {
            $options['--core'] = true;
        }
        if ($this->option('custom')) {
            $options['--custom'] = true;
        }
        if ($this->option('disabled')) {
            $options['--disabled'] = true;
        }
        if ($this->option('group-filter')) {
            $options['--group'] = $this->option('group-filter');
        }
        if ($this->option('verbose')) {
            $options['--verbose'] = true;
        }
        if ($this->option('show-schema')) {
            $options['--show-schema'] = true;
        }
        if ($this->option('show-transform')) {
            $options['--show-transform'] = true;
        }
        if ($this->option('group')) {
            $options['--group'] = $this->option('group');
        }
        if ($this->option('with-media')) {
            $options['--with-media'] = true;
        }
        if ($this->option('order')) {
            $options['--order'] = $this->option('order');
        }

        return ArtisanFacade::call($command, array_merge($arguments, $options));
    }
}

