<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('actor');
            $table->string('actor_type_label')->nullable();
            $table->string('action');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['category', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};