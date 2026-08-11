<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resources are the core of the platform.
     * One resource belongs to one teacher and is targeted at groups
     * via the resource_targets pivot (single / multiple / stage / gender / all).
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academic_term_id')->nullable()->constrained('academic_terms')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // pdf, image, zip, google_drive, external_link, video_link, other
            $table->string('file_path')->nullable(); // local upload (pdf/image/zip/other)
            $table->string('url')->nullable(); // google_drive / external_link / video_link
            $table->date('publish_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('pinned')->default(false);
            $table->string('status')->default('visible'); // visible | hidden | archived
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};