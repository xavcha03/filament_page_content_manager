<?php

namespace Xavcha\PageContentManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AddPageDetailColumnsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-content-manager:add-page-detail 
                            {table : Le nom de la table}
                            {--after= : La colonne après laquelle ajouter les nouvelles colonnes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée une migration pour ajouter les colonnes SEO et Content à une table existante';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $table = $this->argument('table');
        $after = $this->option('after') ?? 'id';

        $timestamp = now()->format('Y_m_d_His');
        $migrationName = "add_page_detail_columns_to_{$table}_table";
        $className = Str::studly($migrationName);

        $stubPath = __DIR__ . '/../../database/migrations/2026_01_08_105749_add_page_detail_columns_to_table.php.stub';
        
        if (!File::exists($stubPath)) {
            $this->error('Le fichier stub de migration n\'existe pas.');
            return Command::FAILURE;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('{{ table }}', $table, $stub);
        $stub = str_replace('{{ after_column }}', $after, $stub);

        $migrationPath = database_path("migrations/{$timestamp}_{$migrationName}.php");
        
        File::put($migrationPath, $stub);

        $this->info("Migration créée : {$migrationPath}");
        $this->info("N'oubliez pas d'ajouter le trait HasPageDetail à votre modèle et de mettre à jour le fillable avec 'seo_title', 'seo_description', 'content'.");

        return Command::SUCCESS;
    }
}




