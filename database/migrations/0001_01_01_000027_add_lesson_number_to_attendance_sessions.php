<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the lesson number to attendance sessions.
     * The teacher enters this when starting attendance for a group.
     */
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->unsignedInteger('lesson_number')->nullable()->after('study_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn('lesson_number');
        });
    }
};