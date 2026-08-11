<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attach term + card identity to existing structures.
     * - StudyGroup belongs to one AcademicTerm.
     * - GroupEnrollment belongs to a term and owns a card_id + qr_token.
     * - AttendanceSession references the active term.
     */
    public function up(): void
    {
        Schema::table('study_groups', function (Blueprint $table) {
            $table->foreignId('academic_term_id')->nullable()->after('teacher_id')
                ->constrained('academic_terms')->onDelete('cascade');
        });

        Schema::table('group_enrollments', function (Blueprint $table) {
            $table->foreignId('academic_term_id')->nullable()->after('group_id')
                ->constrained('academic_terms')->onDelete('cascade');
            $table->string('card_id')->nullable()->after('status');
            $table->string('qr_token')->nullable()->after('card_id');
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->foreignId('academic_term_id')->nullable()->after('teacher_id')
                ->constrained('academic_terms')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropForeign(['academic_term_id']);
            $table->dropColumn('academic_term_id');
        });

        Schema::table('group_enrollments', function (Blueprint $table) {
            $table->dropForeign(['academic_term_id']);
            $table->dropColumn(['academic_term_id', 'card_id', 'qr_token']);
        });

        Schema::table('study_groups', function (Blueprint $table) {
            $table->dropForeign(['academic_term_id']);
            $table->dropColumn('academic_term_id');
        });
    }
};