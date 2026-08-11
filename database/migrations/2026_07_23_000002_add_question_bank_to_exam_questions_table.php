<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 23 — link exam_questions to question_bank while preserving existing data.
     *
     * Existing exam questions (created before Question Bank) will have
     * question_bank_id = NULL. They remain readable and functional.
     * New questions from the bank will have a non-null link.
     */
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable()->constrained('question_bank')->onDelete('set null');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['question_bank_id']);
            $table->dropColumn('question_bank_id');
        });
    }
};