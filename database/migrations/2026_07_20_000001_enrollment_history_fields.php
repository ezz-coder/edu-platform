<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 20 — Enrollment History enrichment.
     *
     * - action: typed lifecycle event (registered | moved | restored |
     *   removed | attendance_removal). Immutable audit label.
     * - performed_by: human-readable actor (teacher / assistant name) at
     *   the moment the event happened. Never edited or deleted.
     */
    public function up(): void
    {
        Schema::table('enrollment_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollment_histories', 'action')) {
                $table->string('action')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('enrollment_histories', 'performed_by')) {
                $table->string('performed_by')->nullable()->after('action');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_histories', function (Blueprint $table) {
            if (Schema::hasColumn('enrollment_histories', 'performed_by')) {
                $table->dropColumn('performed_by');
            }
            if (Schema::hasColumn('enrollment_histories', 'action')) {
                $table->dropColumn('action');
            }
        });
    }
};