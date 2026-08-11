<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A student's attempt at an exam. One running attempt at a time.
     */
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->integer('score')->default(0);
            $table->integer('total_marks')->default(0);
            $table->string('status')->default('running'); // running | finished | cancelled
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};