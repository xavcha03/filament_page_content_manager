<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained('media_files')->onDelete('cascade');
            $table->string('model_type'); // Type du modèle (polymorphique)
            $table->unsignedBigInteger('model_id'); // ID du modèle (polymorphique)
            $table->string('collection_name')->default('default'); // 'images', 'documents', etc.
            $table->unsignedInteger('order')->default(0); // Pour ordre dans galeries
            $table->json('custom_properties')->nullable(); // Propriétés spécifiques au lien
            $table->boolean('is_primary')->default(false); // Pour singleFile collections
            $table->timestamps();
            $table->softDeletes();

            // Index composite pour requêtes efficaces
            $table->index(['model_type', 'model_id', 'collection_name']);
            $table->index('media_file_id');
            $table->index('collection_name');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_attachments');
    }
};





