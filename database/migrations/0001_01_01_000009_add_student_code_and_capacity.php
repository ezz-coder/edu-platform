<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * - student_code: permanent, unique, human-readable code for students.
     * - max_students: capacity limit per study group (always > 0).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'student_code')) {
                $table->string('student_code')->nullable()->unique()->after('teacher_code');
            }
        });

        Schema::table('study_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('study_groups', 'max_students')) {
                $table->unsignedInteger('max_students')->default(30)->after('grade_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'student_code')) {
                $table->dropColumn('student_code');
            }
        });

        Schema::table('study_groups', function (Blueprint $table) {
            if (Schema::hasColumn('study_groups', 'max_students')) {
                $table->dropColumn('max_students');
            }
        });
    }
};