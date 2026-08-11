<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add type to reservations and convert status values.
     * Types: printed_notes, books, booklets, practice_sheets, external_material, other
     * Status: draft, open, closed, archived
     */
    public function up(): void
    {
        // Add type column
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('type')->default('other')->after('description');
        });

        // Convert existing statuses: 'visible' -> 'open', keep 'closed' as is
        \Illuminate\Support\Facades\DB::table('reservations')
            ->where('status', 'visible')
            ->update(['status' => 'open']);
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Convert 'open' back to 'visible' for rollback
        \Illuminate\Support\Facades\DB::table('reservations')
            ->where('status', 'open')
            ->update(['status' => 'visible']);
    }
};