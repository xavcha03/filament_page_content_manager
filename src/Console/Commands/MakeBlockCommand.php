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
                            {--group= : Le groupe UI (Layout/Contenu/Média/Conversion/Social proof/Autres — aliases: layout, content, media, conversion, social, other)}
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
                $group = 'Autres';
            } elseif (class_exists(\Laravel\Prompts\Prompt::class)) {
                $group = \Laravel\Prompts\select(
                    label: 'Quelle catégorie ?',
                    options: [
                        'Layout' => 'Layout',
                        'Contenu' => 'Contenu',
                        'Média' => 'Média',
                        'Conversion' => 'Conversion',
                        'Social proof' => 'Social proof',
                        'Autres' => 'Autres',
                    ],
                    default: 'Autres'
                );
            } else {
                $group = $this->choice(
                    'Quelle catégorie ?',
                    ['Layout', 'Contenu', 'Média', 'Conversion', 'Social proof', 'Autres'],
                    'Autres'
                );
            }
        }

        $group = $this->normalizeGroupLabel($group);

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
        try {
            $this->generateBlockFile($blockName, $type, $namespace, $group, $withMedia, $order, $filePath);
        } catch (\Throwable $e) {
            $this->error("❌ Erreur lors de la création du bloc : {$e->getMessage()}");
            $this->comment("Vérifiez les permissions du répertoire : " . dirname($filePath));
            return Command::FAILURE;
        }

        $this->info("✅ Bloc créé avec succès !");
        $this->line("📁 {$filePath}");
        $this->newLine();
        $this->comment("📝 Prochaines étapes :");
        $this->line("   1. Implémentez la méthode transform() avec votre logique");
        $this->line("   2. Ajoutez vos champs dans la méthode make()");
        $this->line("   3. (Optionnel) Preview : resources/images/block-previews/{$type}.webp");
        if ($withMedia) {
            $this->line("   4. Utilisez les méthodes du trait HasMediaTransformation pour les médias");
        }
        $this->newLine();

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
        if (empty(trim($name))) {
            return 'Le nom du bloc ne peut pas être vide.';
        }

        // Vérifier la longueur minimale
        if (strlen(trim($name)) < 2) {
            return 'Le nom du bloc doit contenir au moins 2 caractères.';
        }

        // Vérifier la longueur maximale
        if (strlen($name) > 50) {
            return 'Le nom du bloc ne peut pas dépasser 50 caractères.';
        }

        $type = BlockCommandHelper::toKebabCase($name);

        // Vérifier que la conversion a produit quelque chose
        if (empty($type)) {
            return 'Le nom du bloc ne peut contenir que des lettres, des chiffres, des espaces et des tirets.';
        }

        // Vérifier les caractères valides après conversion
        if (!preg_match('/^[a-z0-9-]+$/', $type)) {
            return 'Le nom du bloc ne peut contenir que des lettres, des chiffres et des tirets. Exemple : "mon-bloc" ou "video_player".';
        }

        // Vérifier qu'il ne commence/termine pas par un tiret
        if (str_starts_with($type, '-') || str_ends_with($type, '-')) {
            return 'Le nom du bloc ne peut pas commencer ou terminer par un tiret.';
        }

        // Vérifier qu'il n'y a pas de tirets consécutifs
        if (str_contains($type, '--')) {
            return 'Le nom du bloc ne peut pas contenir de tirets consécutifs.';
        }

        // Vérifier si le bloc existe déjà
        if (BlockCommandHelper::blockExists($registry, $type)) {
            $suggestion = BlockCommandHelper::findSimilarBlocks($registry, $type, 1);
            $message = "Un bloc avec le type '{$type}' existe déjà.";
            if (!empty($suggestion) && $suggestion[0]['type'] !== $type) {
                $message .= " Peut-être vouliez-vous dire '{$suggestion[0]['type']}' ?";
            }
            return $message;
        }

        // Vérifier si le fichier existe déjà
        $blockName = BlockCommandHelper::toPascalCase($name);
        if (BlockCommandHelper::blockFileExists($blockName)) {
            return "Un fichier pour le bloc '{$blockName}' existe déjà. Utilisez --force pour l'écraser.";
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
        $stub = str_replace('{{ description }}', "Bloc {$label}.", $stub);
        $stub = str_replace('{{ order }}', (string) $order, $stub);
        $stub = str_replace('{{ hasMediaUse }}', $hasMediaUse, $stub);
        $stub = str_replace('{{ hasMediaTrait }}', $hasMediaTrait, $stub);

        // Créer le dossier si nécessaire
        $directory = dirname($filePath);
        if (!File::exists($directory)) {
            try {
                File::makeDirectory($directory, 0755, true);
            } catch (\Throwable $e) {
                throw new \RuntimeException("Impossible de créer le répertoire {$directory} : {$e->getMessage()}");
            }
        }

        // Vérifier les permissions d'écriture
        if (!is_writable($directory)) {
            throw new \RuntimeException("Le répertoire {$directory} n'est pas accessible en écriture");
        }

        // Écrire le fichier
        try {
            File::put($filePath, $stub);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Impossible d'écrire le fichier {$filePath} : {$e->getMessage()}");
        }
    }

    /**
     * Normalise les aliases de groupe vers les labels UI Filament.
     */
    protected function normalizeGroupLabel(string $group): string
    {
        return match (mb_strtolower(trim($group))) {
            'layout' => 'Layout',
            'content', 'contenu' => 'Contenu',
            'media', 'média' => 'Média',
            'conversion', 'forms', 'form' => 'Conversion',
            'social', 'social proof', 'social_proof' => 'Social proof',
            'other', 'autres', 'autre' => 'Autres',
            default => $group,
        };
    }

    /**
     * Retourne l'icône par défaut selon le groupe.
     *
     * @param string $group
     * @return string
     */
    protected function getIconForGroup(string $group): string
    {
        return match ($this->normalizeGroupLabel($group)) {
            'Layout' => 'squares-2x2',
            'Contenu' => 'document-text',
            'Média' => 'photo',
            'Conversion' => 'arrow-right-circle',
            'Social proof' => 'chat-bubble-left-right',
            default => 'cube',
        };
    }
}

