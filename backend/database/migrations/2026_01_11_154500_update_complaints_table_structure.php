<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'nama_pelapor')) {
                $table->dropColumn('nama_pelapor');
            }
            if (Schema::hasColumn('complaints', 'jenis_pengaduan')) {
                $table->dropColumn('jenis_pengaduan');
            }
            if (Schema::hasColumn('complaints', 'konselor')) {
                $table->dropColumn('konselor');
            }
            if (Schema::hasColumn('complaints', 'jadwal_konseling')) {
                $table->dropColumn('jadwal_konseling');
            }
        });

        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'user_id')) {
                $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete()->comment('Pelapor');
            }

            if (! Schema::hasColumn('complaints', 'counselor_id')) {
                $table->foreignId('counselor_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete()->comment('Konselor yang menangani');
            }

            if (! Schema::hasColumn('complaints', 'violence_category_id')) {
                $table->foreignId('violence_category_id')->nullable()->after('counselor_id')->constrained('violence_categories')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'title')) {
                $table->string('title')->after('violence_category_id')->comment('Judul pengaduan');
            }

            if (! Schema::hasColumn('complaints', 'description')) {
                $table->text('description')->nullable()->after('title')->comment('Deskripsi detail pengaduan');
            }

            $table->enum('status', [
                'baru',
                'diproses',
                'selesai'
            ])->default('baru')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('complaints', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('complaints', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('complaints', 'violence_category_id')) {
                $table->dropForeign(['violence_category_id']);
                $table->dropColumn('violence_category_id');
            }

            if (Schema::hasColumn('complaints', 'counselor_id')) {
                $table->dropForeign(['counselor_id']);
                $table->dropColumn('counselor_id');
            }

            if (Schema::hasColumn('complaints', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'nama_pelapor')) {
                $table->string('nama_pelapor')->nullable();
            }
            if (! Schema::hasColumn('complaints', 'jenis_pengaduan')) {
                $table->string('jenis_pengaduan')->nullable();
            }
            if (! Schema::hasColumn('complaints', 'konselor')) {
                $table->string('konselor')->nullable();
            }
            if (! Schema::hasColumn('complaints', 'jadwal_konseling')) {
                $table->dateTime('jadwal_konseling')->nullable();
            }

            $table->enum('status', [
                'baru',
                'diproses',
                'selesai'
            ])->default('baru');
        });
    }
};
