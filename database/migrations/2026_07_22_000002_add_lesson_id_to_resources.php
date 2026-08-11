<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasColumn = Schema::hasColumn('resources', 'lesson_id');

        if (! $hasColumn) {
            Schema::table('resources', function (Blueprint $table) {
                $table->foreignId('lesson_id')->nullable()->after('academic_term_id')->constrained('lessons');
            });
        }
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropColumn('lesson_id');
        });
    }
};