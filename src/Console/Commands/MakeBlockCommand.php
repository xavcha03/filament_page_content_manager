<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;

class MakeBlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:make-block 
                            {name? : Le nom du bloc (kebab-case)}
                            {--group= : Le groupe du bloc (content/media/forms/other)}
                            {--with-media : Utiliser le trait HasMediaTransformation}
                            {--order= : L\'ordre d\'affichage (défaut: 100)}
                            {--force : Écraser le fichier s\'il existe déjà}
                            {--namespace= : Le namespace personnalisé (défaut: App\\Blocks\\Custom)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée un nouveau bloc personnalisé';

    /**
     * Execute the console command.
     */
    public function handle(BlockRegistry $registry): int
    {
        $isNonInteractive = BlockCommandHelper::isNonInteractive($this);

        // Récupérer le nom du bloc
        $name = $this->argument('name');

        if (!$name) {
            if ($isNonInteractive) {
                $this->error('Le paramètre "name" est requis en mode non-interactif.');
                return Command::FAILURE;
            }

            // Mode interactif
            if (class_exists(\Laravel\Prompts\Prompt::class)) {
                $name = \Laravel\Prompts\text(
                    label: 'Quel est le nom de votre bloc ?',
                    required: true,
                    validate: fn ($value) => $this->validateBlockName($value, $registry)
                );
            } else {
                $name = $this->ask('Quel est le nom de votre bloc ?');
                $validation = $this->validateBlockName($name, $registry);
                if ($validation !== null) {
                    $this->error($validation);
                    return Command::FAILURE;
                }
            }
        } else {
            // Valider le nom fourni
            $validation = $this->validateBlockName($name, $registry);
            if ($validation !== null) {
                $this->error($validation);
                return Command::FAILURE;
            }
        }

        $type = BlockCommandHelper::toKebabCase($name);
        $blockName = BlockCommandHelper::toPascalCase($name);
        $namespace = $this->option('namespace') ?: 'App\\Blocks\\Custom';

        // Vérifier si le fichier existe déjà
        $filePath = app_path("Blocks/Custom/{$blockName}Block.php");
        if (File::exists($filePath) && !$this->option('force')) {
            if ($isNonInteractive) {
                $this->error("Le bloc {$type} existe déjà. Utilisez --force pour l'écraser.");
                return Command::FAILURE;
            }

            if (class_exists(\Laravel\Prompts\Prompt::class)) {
                $overwrite = \Laravel\Prompts\confirm(
                    label: "Le bloc {$type} existe déjà. Voulez-vous l'écraser ?",
                    default: false
                );
            } else {
                $overwrite = $this->confirm("Le bloc {$type} existe déjà. Voulez-vous l'écraser ?", false);
            }

            if (!$overwrite) {
                $this->info('Opération annulée.');
                return Command::SUCCESS;
            }
        }

        // Récupérer les options
        $group = $this->option('group');
        $withMedia = $this->option('with-media');
        $order = (int) ($this->option('order') ?: 100);

        if (!$group) {
            if ($isNonInteractive) {
                $group = 'other';
            } elseif (class_exists(\Laravel\Prompts\Prompt::class)) {
                $group = \Laravel\Prompts\select(
                    label: 'Quelle catégorie ?',
                    options: [
                        'content' => 'Content',
                        'media' => 'Media',
                        'forms' => 'Forms',
                        'other' => 'Other',
                    ],
                    default: 'other'
                );
            } else {
                $group = $this->choice(
                    'Quelle catégorie ?',
                    ['content', 'media', 'forms', 'other'],
                    'other'
                );
            }
        }

        if (!$isNonInteractive && !$this->option('with-media')) {
            if (class_exists(\Laravel\Prompts\Prompt::class)) {
                $withMedia = \Laravel\Prompts\confirm(
                    label: 'Voulez-vous utiliser le trait HasMediaTransformation ?',
                    default: false
                );
            } else {
                $withMedia = $this->confirm('Voulez-vous utiliser le trait HasMediaTransformation ?', false);
            }
        }

        // Générer le fichier
        $this->generateBlockFile($blockName, $type, $namespace, $group, $withMedia, $order, $filePath);

        $this->info("✅ Bloc créé avec succès !");
        $this->line("📁 {$filePath}");
        $this->comment("📝 N'oubliez pas d'implémenter la méthode transform() !");

        return Command::SUCCESS;
    }

    /**
     * Valide le nom du bloc.
     *
     * @param string $name
     * @param BlockRegistry $registry
     * @return string|null Message d'erreur ou null si valide
     */
    protected function validateBlockName(string $name, BlockRegistry $registry): ?string
    {
        if (empty($name)) {
            return 'Le nom du bloc ne peut pas être vide.';
        }

        $type = BlockCommandHelper::toKebabCase($name);

        if (BlockCommandHelper::blockExists($registry, $type)) {
            return "Un bloc avec le type '{$type}' existe déjà.";
        }

        // Vérifier les caractères valides
        if (!preg_match('/^[a-z0-9-]+$/', $type)) {
            return 'Le nom du bloc ne peut contenir que des lettres minuscules, des chiffres et des tirets.';
        }

        return null;
    }

    /**
     * Génère le fichier du bloc.
     *
     * @param string $blockName
     * @param string $type
     * @param string $namespace
     * @param string $group
     * @param bool $withMedia
     * @param int $order
     * @param string $filePath
     * @return void
     */
    protected function generateBlockFile(
        string $blockName,
        string $type,
        string $namespace,
        string $group,
        bool $withMedia,
        int $order,
        string $filePath
    ): void {
        // Lire le stub
        $stubPath = __DIR__ . '/../Stubs/Block.stub';
        $stub = File::get($stubPath);

        // Préparer les remplacements
        $label = ucfirst(str_replace(['-', '_'], ' ', $type));
        $icon = $this->getIconForGroup($group);

        $hasMediaUse = $withMedia
            ? "use Xavcha\PageContentManager\Blocks\Concerns\HasMediaTransformation;"
            : "";

        $hasMediaTrait = $withMedia
            ? "    use HasMediaTransformation;"
            : "";

        // Remplacer les placeholders
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ blockName }}', $blockName, $stub);
        $stub = str_replace('{{ type }}', $type, $stub);
        $stub = str_replace('{{ label }}', $label, $stub);
        $stub = str_replace('{{ icon }}', $icon, $stub);
        $stub = str_replace('{{ group }}', $group, $stub);
        $stub = str_replace('{{ order }}', $order, $stub);
        $stub = str_replace('{{ hasMediaUse }}', $hasMediaUse, $stub);
        $stub = str_replace('{{ hasMediaTrait }}', $hasMediaTrait, $stub);

        // Créer le dossier si nécessaire
        $directory = dirname($filePath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Écrire le fichier
        File::put($filePath, $stub);
    }

    /**
     * Retourne l'icône par défaut selon le groupe.
     *
     * @param string $group
     * @return string
     */
    protected function getIconForGroup(string $group): string
    {
        return match ($group) {
            'content' => 'document-text',
            'media' => 'photo',
            'forms' => 'clipboard-document-list',
            default => 'cube',
        };
    }
}

