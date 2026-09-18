<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('challenge')->nullable()->after('description');
            $table->text('solution')->nullable()->after('challenge');
            $table->text('impact')->nullable()->after('solution');
            $table->string('duration')->nullable()->after('project_date');
            $table->string('surface')->nullable()->after('duration');
            $table->string('budget')->nullable()->after('surface');
            $table->text('testimonial_quote')->nullable()->after('client_publishable');
            $table->string('testimonial_author')->nullable()->after('testimonial_quote');
            $table->decimal('latitude', 10, 7)->nullable()->after('testimonial_author');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'challenge',
                'solution',
                'impact',
                'duration',
                'surface',
                'budget',
                'testimonial_quote',
                'testimonial_author',
                'latitude',
                'longitude',
            ]);
        });
    }
};
