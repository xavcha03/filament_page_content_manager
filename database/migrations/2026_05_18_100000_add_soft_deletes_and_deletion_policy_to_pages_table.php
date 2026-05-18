<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('deleted_response_type')->nullable()->after('seo_noindex');
            $table->foreignId('redirect_target_page_id')
                ->nullable()
                ->after('deleted_response_type')
                ->constrained('pages')
                ->nullOnDelete();
            $table->string('redirect_target_url', 2048)->nullable()->after('redirect_target_page_id');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['redirect_target_page_id']);
            $table->dropColumn([
                'deleted_at',
                'deleted_response_type',
                'redirect_target_page_id',
                'redirect_target_url',
            ]);
        });
    }
};
