<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-student attendance records for a session.
     * Saved immediately on scan (no waiting for session close).
     * attendance_type: regular | late
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained('attendance_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('study_groups')->onDelete('cascade');
            $table->string('attendance_type')->default('regular'); // regular | late
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id'], 'att_session_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};