<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simple rollback flag for the End Term wizard.
     * Stores enough info to reverse an end-term execution.
     */
    public function up(): void
    {
        Schema::create('term_rollbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('old_term_id')->constrained('academic_terms')->onDelete('cascade');
            $table->foreignId('new_term_id')->constrained('academic_terms')->onDelete('cascade');
            $table->string('mode'); // keep_groups | new_groups
            $table->json('moved_group_ids')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_rollbacks');
    }
};