<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 61 — Bug fix: the original migration `2026_07_25_000001`
 * was recorded as ran but the `can_manage_grades` column was never
 * actually added to the table. This adds it so the Grades module
 * can enforce the assistant permission correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assistant_permissions', 'can_manage_grades')) {
            Schema::table('assistant_permissions', function (Blueprint $table) {
                $table->boolean('can_manage_grades')->default(false)->after('can_manage_exams');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assistant_permissions', 'can_manage_grades')) {
            Schema::table('assistant_permissions', function (Blueprint $table) {
                $table->dropColumn('can_manage_grades');
            });
        }
    }
};