<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lightweight in-app notifications (database only).
     * No websockets / emails / queues.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('study_groups')->onDelete('cascade');
            $table->string('title');
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};