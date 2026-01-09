<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('counseling_schedules', function (Blueprint $table) {
            $table->id();

            // relasi ke users (klien)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('jenis_pengaduan');
            $table->dateTime('tanggal_waktu');

            $table->enum('metode', ['tatap_muka', 'zoom']);

            $table->enum('status', [
                'menunggu_konfirmasi',
                'dikonfirmasi',
                'dibatalkan'
            ])->default('menunggu_konfirmasi');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_schedules');
    }
};
