<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Academic terms are the backbone: groups, enrollments, attendance
     * and (later) resources/exams all belong to one term.
     * Only one ACTIVE term per teacher at a time.
     */
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('year');
            $table->unsignedTinyInteger('term_number')->default(1);
            $table->string('status')->default('active'); // active | archived
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};