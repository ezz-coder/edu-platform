<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 23 — Exam lifecycle + settings.
     *
     * Replace simple visible/hidden status with draft/published/closed.
     * Add result + review controls.
     * Soft-close keeps history readable.
     */
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('show_result_immediately')->default(false)->after('ends_at');
            $table->boolean('allow_review_after_finish')->default(true)->after('show_result_immediately');
            $table->dateTime('archived_at')->nullable()->after('allow_review_after_finish');
        });

        // Convert old statuses: visible -> published, hidden -> draft
        \DB::table('exams')->where('status', 'visible')->update(['status' => 'published']);
        \DB::table('exams')->where('status', 'hidden')->update(['status' => 'draft']);
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['show_result_immediately', 'allow_review_after_finish', 'archived_at']);
        });
    }
};