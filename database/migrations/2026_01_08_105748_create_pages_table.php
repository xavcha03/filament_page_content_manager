<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['home', 'standard'])->index();
            $table->string('slug', 255)->unique()->index();
            $table->string('title', 255);
            $table->json('content');
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // Créer la page Home par défaut
        DB::table('pages')->insert([
            'type' => 'home',
            'slug' => 'home',
            'title' => 'Accueil',
            'content' => json_encode([
                'sections' => [],
                'metadata' => [
                    'schema_version' => 1,
                ],
            ]),
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};



