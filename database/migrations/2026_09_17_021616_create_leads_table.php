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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_type');
            $table->string('budget')->nullable();
            $table->text('message');
            $table->string('status')->default('nouveau')->index();
            $table->string('source')->default('site');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
