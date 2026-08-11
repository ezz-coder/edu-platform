<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lessons are teacher-owned containers for resources.
     * - belongs to a teacher and a study group
     * - ordered by display_order within teacher's scope
     * - active status controls visibility
     */
    public function up(): void
    {
        if (Schema::hasTable('lessons')) {
            return;
        }

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('study_groups')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['teacher_id', 'display_order']);
            $table->index(['group_id', 'is_active']);
        });

    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropColumn('lesson_id');
        });

        Schema::dropIfExists('lessons');
    }
};