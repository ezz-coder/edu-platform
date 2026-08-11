<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: which groups an exam targets (reuse pattern from reservations).
     */
    public function up(): void
    {
        Schema::create('exam_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('study_groups')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['exam_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_groups');
    }
};