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
    Schema::create('materials', function (Blueprint $table) {
        $table->id();
        $table->string('judul');
        $table->enum('tipe', ['pdf', 'link']);
        $table->string('file_path')->nullable(); // pdf path
        $table->string('link')->nullable();      // link materi
        $table->string('kategori')->nullable();
        $table->foreignId('uploaded_by')
              ->constrained('users')
              ->onDelete('cascade');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
