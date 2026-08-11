<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 23 — Question Bank for reusable exam questions.
     *
     * Each question belongs to exactly one teacher and can optionally belong
     * to a lesson or study group. Questions support multiple types and
     * soft deletes for recycle bin support.
     */
    public function up(): void
    {
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->onDelete('set null');
            $table->foreignId('group_id')->nullable()->constrained('study_groups')->onDelete('set null');
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('type'); // mcq | true_false | fill_blank | short_answer
            $table->string('correct_answer')->nullable();
            $table->json('options')->nullable(); // For MCQ: {"a": "...", "b": "...", ...}
            $table->text('explanation')->nullable();
            $table->string('difficulty')->default('medium'); // easy | medium | hard
            $table->integer('points')->default(1);
            $table->string('status')->default('active'); // active | inactive
            $table->softDeletes();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['teacher_id', 'type']);
            $table->index(['teacher_id', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank');
    }
};