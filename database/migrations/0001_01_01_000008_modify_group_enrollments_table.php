<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a target-group column to support transfer requests without a
     * separate table. SQLite stores the enum as TEXT, so no type change
     * is needed; the new status value is simply accepted by the model.
     */
    public function up(): void
    {
        Schema::table('group_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('group_enrollments', 'requested_group_id')) {
                $table->foreignId('requested_group_id')
                    ->nullable()
                    ->constrained('study_groups')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('group_enrollments', 'requested_group_id')) {
                $table->dropConstrainedForeignId('requested_group_id');
            }
        });
    }
};