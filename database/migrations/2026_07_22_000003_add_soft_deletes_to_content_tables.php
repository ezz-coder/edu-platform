<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unified soft-delete for content tables.
     * - lessons
     * - resources
     * - exams
     * - reservations
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'deleted_at')) {
                $table->softDeletes()->after('is_active');
            }
        });

        Schema::table('resources', function (Blueprint $table) {
            if (! Schema::hasColumn('resources', 'deleted_at')) {
                $table->softDeletes()->after('status');
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'deleted_at')) {
                $table->softDeletes()->after('status');
            }
        });

        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'deleted_at')) {
                $table->softDeletes()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('resources', function (Blueprint $table) {
            if (Schema::hasColumn('resources', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};