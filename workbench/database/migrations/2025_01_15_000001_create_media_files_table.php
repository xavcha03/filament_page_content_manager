<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('file_name'); // Nom original du fichier
            $table->string('stored_name'); // Nom stocké sur le disque (peut être hashé)
            $table->string('disk')->default('public'); // 'local', 'public', 's3', etc.
            $table->string('path'); // Chemin relatif sur le disque
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size'); // Taille en bytes
            $table->unsignedInteger('width')->nullable(); // Pour images
            $table->unsignedInteger('height')->nullable(); // Pour images
            $table->unsignedInteger('duration')->nullable(); // Pour vidéos/audio en secondes
            $table->json('metadata')->nullable(); // EXIF, métadonnées custom
            $table->text('alt_text')->nullable(); // Pour accessibilité
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true); // Pour permissions futures
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index('disk');
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};





