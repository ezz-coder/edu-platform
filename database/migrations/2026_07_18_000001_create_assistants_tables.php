<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 18: Teacher Assistant accounts & permission system.
     *
     * - assistants: one assistant belongs to one teacher; cannot self-register.
     * - assistant_permissions: boolean perms explicitly granted by the teacher.
     *   Only the selected permissions are stored (row per assistant).
     */
    public function up(): void
    {
        Schema::create('assistants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('status')->default('active'); // active | inactive
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });

        Schema::create('assistant_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_id')->constrained('assistants')->onDelete('cascade');
            $table->boolean('can_take_attendance')->default(false);
            $table->boolean('can_manage_students')->default(false);
            $table->boolean('can_upload_resources')->default(false);
            $table->boolean('can_manage_reservations')->default(false);
            $table->boolean('can_restore_students')->default(false);
            $table->boolean('can_view_groups')->default(false);
            $table->boolean('can_manage_exams')->default(false);
            $table->timestamps();

            $table->unique('assistant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_permissions');
        Schema::dropIfExists('assistants');
    }
};