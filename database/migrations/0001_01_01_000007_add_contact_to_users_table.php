<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Mobile is used for login (teacher numbers are globally unique,
            // student numbers may repeat across different teachers).
            $table->string('mobile')->nullable()->after('name');
            // Parent mobile is collected only for students.
            $table->string('parent_mobile')->nullable()->after('mobile');
            // Teacher Code is auto-generated, unique and not editable.
            $table->string('teacher_code')->nullable()->unique()->after('parent_mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'parent_mobile', 'teacher_code']);
        });
    }
};