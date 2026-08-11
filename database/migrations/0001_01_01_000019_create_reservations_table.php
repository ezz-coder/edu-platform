<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Memo / book reservation. Teacher opens a reservation for his groups.
     * Students reserve; teacher tracks remaining quantity.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academic_term_id')->nullable()->constrained('academic_terms')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->date('open_date')->nullable();
            $table->date('close_date')->nullable();
            $table->string('status')->default('visible'); // visible | closed | deleted
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};