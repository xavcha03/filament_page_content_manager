<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->unique(); // Chemin unique (ex: "root/subfolder")
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->onDelete('cascade');
            $table->timestamps();

            $table->index('path');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_folders');
    }
};





