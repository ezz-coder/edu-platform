<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simple targeting relation for a resource.
     * scope: group | stage | gender | all
     * - group:  group_id set
     * - stage:  stage value (grade_level)
     * - gender: gender value (boys/girls/mixed)
     * - all:    targets every group of the teacher
     */
    public function up(): void
    {
        Schema::create('resource_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('resources')->onDelete('cascade');
            $table->string('scope'); // group | stage | gender | all
            $table->foreignId('group_id')->nullable()->constrained('study_groups')->onDelete('cascade');
            $table->string('stage')->nullable();
            $table->string('gender')->nullable();
            $table->timestamps();

            $table->index(['resource_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_targets');
    }
};