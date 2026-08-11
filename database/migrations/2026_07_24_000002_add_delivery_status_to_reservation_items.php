<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add delivery_status to track delivered/not delivered items.
     * delivery_status: pending, delivered
     */
    public function up(): void
    {
        Schema::table('reservation_items', function (Blueprint $table) {
            $table->string('delivery_status')->default('pending')->after('reserved_at');
            $table->timestamp('delivered_at')->nullable()->after('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_items', function (Blueprint $table) {
            $table->dropColumn(['delivery_status', 'delivered_at']);
        });
    }
};