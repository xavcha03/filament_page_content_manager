<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Xavcha\PageContentManager\Console\Helpers\BlockCommandHelper;
use Xavcha\PageContentManager\Experiences\ExperienceRegistry;

class MakeExperienceCommand extends Command
{
    protected $signature = 'page-content-manager:make-experience
                            {name? : Nom de l\'Experience (kebab-case, ex: home-organic)}
                            {--with-media : Utiliser le trait HasMediaTransformation}
                            {--force : Écraser le fichier s\'il existe déjà}
                            {--namespace= : Namespace (défaut: App\\Experiences)}';

    protected $description = 'Crée une nouvelle Experience (schéma de page figé) dans app/Experiences';

    public function handle(ExperienceRegistry $registry): int
    {
        $isNonInteractive = BlockCommandHelper::isNonInteractive($this);

        $name = $this->argument('name');

        if (! $name) {
            if ($isNonInteractive) {
                $this->error('Le paramètre "name" est requis en mode non-interactif.');

                return Command::FAILURE;
            }

            $name = $this->ask('Quel est le nom de votre Experience ? (ex: home-organic)');
        }

        $validation = $this->validateName((string) $name, $registry);
        if ($validation !== null) {
            $this->error($validation);

            return Command::FAILURE;
        }

        $key = BlockCommandHelper::toKebabCase((string) $name);
        $classBase = BlockCommandHelper::toPascalCase((string) $name);
        $className = str_ends_with($classBase, 'Experience') ? $classBase : $classBase . 'Experience';
        $namespace = $this->option('namespace') ?: 'App\\Experiences';
        $filePath = app_path('Experiences/' . $className . '.php');

        if (File::exists($filePath) && ! $this->option('force')) {
            if ($isNonInteractive) {
                $this->error("L'Experience {$key} existe déjà. Utilisez --force pour l'écraser.");

                return Command::FAILURE;
            }

            if (! $this->confirm("Le fichier existe déjà. Écraser ?", false)) {
                $this->info('Opération annulée.');

                return Command::SUCCESS;
            }
        }

        $withMedia = (bool) $this->option('with-media');
        if (! $isNonInteractive && ! $this->option('with-media')) {
            $withMedia = $this->confirm('Utiliser le trait HasMediaTransformation ?', false);
        }

        try {
            $this->generateFile($className, $key, $namespace, $withMedia, $filePath);
        } catch (\Throwable $e) {
            $this->error('Erreur : ' . $e->getMessage());

            return Command::FAILURE;
        }

        $registry->clearCache();

        $this->info('Experience créée avec succès !');
        $this->line("{$filePath}");
        $this->newLine();
        $this->comment('Prochaines étapes :');
        $this->line('  1. Compléter make() avec les champs Filament figés du design');
        $this->line('  2. Compléter transform() pour l\'API / frontend');
        $this->line('  3. Compléter getMcpFields() pour les agents MCP');
        $this->line('  4. Créer le composant Next.js correspondant (voir docs/agent-create-experience.md)');

        return Command::SUCCESS;
    }

    protected function validateName(string $name, ExperienceRegistry $registry): ?string
    {
        if (trim($name) === '') {
            return 'Le nom ne peut pas être vide.';
        }

        $key = BlockCommandHelper::toKebabCase($name);
        if ($key === '' || ! preg_match('/^[a-z0-9-]+$/', $key)) {
            return 'Le nom doit produire une clé kebab-case (lettres, chiffres, tirets).';
        }

        if ($registry->has($key)) {
            return "Une Experience avec la clé '{$key}' existe déjà.";
        }

        return null;
    }

    protected function generateFile(
        string $className,
        string $key,
        string $namespace,
        bool $withMedia,
        string $filePath,
    ): void {
        $stubPath = __DIR__ . '/../Stubs/Experience.stub';
        $stub = File::get($stubPath);

        $label = ucfirst(str_replace(['-', '_'], ' ', $key));

        $hasMediaUse = $withMedia
            ? 'use Xavcha\\PageContentManager\\Blocks\\Concerns\\HasMediaTransformation;'
            : '';

        $hasMediaTrait = $withMedia
            ? '    use HasMediaTransformation;'
            : '';

        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ className }}', $className, $stub);
        $stub = str_replace('{{ key }}', $key, $stub);
        $stub = str_replace('{{ label }}', $label, $stub);
        $stub = str_replace('{{ hasMediaUse }}', $hasMediaUse, $stub);
        $stub = str_replace('{{ hasMediaTrait }}', $hasMediaTrait, $stub);

        $directory = dirname($filePath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($filePath, $stub);
    }
}
