<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per student reservation (a "reserved" record).
     */
    public function up(): void
    {
        Schema::create('reservation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('study_groups')->onDelete('cascade');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamps();

            $table->unique(['reservation_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_items');
    }
};