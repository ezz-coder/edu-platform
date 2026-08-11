<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student identity & lifecycle support for Sprint 17.
     *
     * - group_enrollments: track when a student became inactive so resources
     *   can be visibility-filtered by date (pre-inactivity only). The qr_token
     *   and card_id already exist on this table and are permanent.
     * - enrollment_histories: immutable audit log of group transfers / moves
     *   so prior enrollment data is never destroyed.
     */
    public function up(): void
    {
        Schema::table('group_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('group_enrollments', 'inactivated_at')) {
                $table->timestamp('inactivated_at')->nullable()->after('status');
            }
        });

        if (! Schema::hasTable('enrollment_histories')) {
            Schema::create('enrollment_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->constrained('group_enrollments')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('from_group_id')->nullable()->constrained('study_groups')->onDelete('set null');
                $table->foreignId('to_group_id')->nullable()->constrained('study_groups')->onDelete('set null');
                $table->string('reason'); // transferred | moved | restored | inactivated | unknown
                $table->timestamp('changed_at')->useCurrent();
                $table->timestamps();

                $table->index(['enrollment_id', 'changed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_histories');

        Schema::table('group_enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('group_enrollments', 'inactivated_at')) {
                $table->dropColumn('inactivated_at');
            }
        });
    }
};