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
         Schema::create('complaints', function (Blueprint $table) {
        $table->id();

        $table->string('nama_pelapor');
        $table->string('jenis_pengaduan');

        $table->date('tanggal_laporan');

        $table->enum('status', [
            'baru',
            'diproses',
            'selesai'
        ])->default('baru');

        $table->dateTime('jadwal_konseling')->nullable();
        $table->string('konselor')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
