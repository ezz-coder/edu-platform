<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MCQ questions for an exam.
     */
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->string('type')->default('mcq'); // mcq only for now
            $table->text('question');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->string('correct_answer'); // a | b | c | d
            $table->integer('mark')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['exam_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};