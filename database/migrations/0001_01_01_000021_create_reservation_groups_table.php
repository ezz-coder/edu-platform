<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: which groups a reservation targets.
     */
    public function up(): void
    {
        Schema::create('reservation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('study_groups')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['reservation_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_groups');
    }
};