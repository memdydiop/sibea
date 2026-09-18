<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->string('page_intro_title')->nullable()->after('hero_sort_order');
            $table->text('page_intro_text')->nullable()->after('page_intro_title');
            $table->json('page_cards')->nullable()->after('page_intro_text');
            $table->json('page_figures')->nullable()->after('page_cards');
            $table->string('page_cta_title')->nullable()->after('page_figures');
            $table->text('page_cta_text')->nullable()->after('page_cta_title');
            $table->string('page_cta_label')->nullable()->after('page_cta_text');
        });
    }

    public function down(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropColumn([
                'page_intro_title',
                'page_intro_text',
                'page_cards',
                'page_figures',
                'page_cta_title',
                'page_cta_text',
                'page_cta_label',
            ]);
        });
    }
};
