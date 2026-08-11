<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The ActivityLog model logs an owning entity via owner_id/owner_type
     * (used by assistant/teacher controllers). The original migration only
     * created the actor morphs, so inserts failed with
     * "no column named owner_id". Add the owner morphs here.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->nullableMorphs('owner');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropMorphs('owner');
        });
    }
};
