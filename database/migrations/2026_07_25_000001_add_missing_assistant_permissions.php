<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bug fix: the AssistantPermission model declares `can_manage_lessons`
     * and `can_manage_exams`, and the assistant routes/middleware rely on
     * them, but the original migration never created these columns. This
     * adds the missing columns so permissions can be stored and read.
     * 
     * Also adds `can_manage_grades` permission as required by shared_workflows.md.
     */
    public function up(): void
    {
        Schema::table('assistant_permissions', function (Blueprint $table) {
            $table->boolean('can_manage_lessons')->default(false)->after('can_view_groups');
            $table->boolean('can_manage_exams')->default(false)->after('can_manage_lessons');
            $table->boolean('can_manage_grades')->default(false)->after('can_manage_exams');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_permissions', function (Blueprint $table) {
            $table->dropColumn(['can_manage_lessons', 'can_manage_exams', 'can_manage_grades']);
        });
    }
};