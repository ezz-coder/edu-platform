<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend the notification table with a TYPE identity, a stored
     * destination link and optional assistant ownership.
     *
     * This powers the Smart Notification Center (Sprint 19).
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->default('system')->after('group_id');
            $table->text('link')->nullable()->after('message');
            $table->foreignId('assistant_id')->nullable()->after('student_id')
                ->constrained('assistants')->onDelete('cascade');
            $table->index(['type']);
            $table->index(['assistant_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['assistant_id', 'read_at']);
            $table->dropConstrainedForeignId('assistant_id');
            $table->dropColumn(['type', 'link']);
        });
    }
};