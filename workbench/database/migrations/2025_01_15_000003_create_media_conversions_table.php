<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained('media_files')->onDelete('cascade');
            $table->string('conversion_name'); // 'thumb', 'medium', 'large', etc.
            $table->string('file_name');
            $table->string('disk')->default('public'); // Peut être différent du fichier original
            $table->string('path');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedTinyInteger('quality')->default(85); // Pour JPEG
            $table->string('format')->default('jpg'); // 'jpg', 'webp', 'png', etc.
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            // Index composite pour requêtes efficaces
            $table->unique(['media_file_id', 'conversion_name']);
            $table->index('media_file_id');
            $table->index('conversion_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_conversions');
    }
};





