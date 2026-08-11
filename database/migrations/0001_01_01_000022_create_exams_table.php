<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exams belong to a teacher and an academic term.
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academic_term_id')->nullable()->constrained('academic_terms')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('visible'); // visible | hidden
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};