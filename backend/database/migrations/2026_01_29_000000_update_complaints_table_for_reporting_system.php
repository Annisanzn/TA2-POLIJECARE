<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Tambahkan field untuk korban_type dan korban_nama
            if (!Schema::hasColumn('complaints', 'victim_type')) {
                $table->enum('victim_type', ['self', 'other'])->default('self')->after('description')->comment('Jenis korban: diri sendiri atau orang lain');
            }
            
            if (!Schema::hasColumn('complaints', 'victim_name')) {
                $table->string('victim_name')->nullable()->after('victim_type')->comment('Nama korban jika bukan diri sendiri');
            }
            
            if (!Schema::hasColumn('complaints', 'victim_relationship')) {
                $table->string('victim_relationship')->nullable()->after('victim_name')->comment('Hubungan dengan pelapor');
            }
            
            if (!Schema::hasColumn('complaints', 'chronology')) {
                $table->longText('chronology')->nullable()->after('victim_relationship')->comment('Kronologi kejadian detail');
            }
            
            // Field untuk audit trail
            if (!Schema::hasColumn('complaints', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('chronology')->comment('IP address pembuat laporan');
            }
            
            if (!Schema::hasColumn('complaints', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address')->comment('Browser info pembuat laporan');
            }
            
            if (!Schema::hasColumn('complaints', 'report_reference')) {
                $table->string('report_reference')->unique()->after('id')->comment('Nomor referensi laporan');
            }
            
            // Field tambahan untuk kebutuhan institusi
            if (!Schema::hasColumn('complaints', 'urgency_level')) {
                $table->enum('urgency_level', ['low', 'medium', 'high', 'critical'])->default('medium')->after('status')->comment('Tingkat urgensi laporan');
            }
            
            if (!Schema::hasColumn('complaints', 'is_anonymous')) {
                $table->boolean('is_anonymous')->default(false)->after('urgency_level')->comment('Laporan anonim');
            }
            
            if (!Schema::hasColumn('complaints', 'incident_date')) {
                $table->date('incident_date')->nullable()->after('is_anonymous')->comment('Tanggal kejadian');
            }
            
            if (!Schema::hasColumn('complaints', 'incident_location')) {
                $table->string('incident_location')->nullable()->after('incident_date')->comment('Lokasi kejadian');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $columnsToDrop = [
                'victim_type',
                'victim_name', 
                'victim_relationship',
                'chronology',
                'ip_address',
                'user_agent',
                'report_reference',
                'urgency_level',
                'is_anonymous',
                'incident_date',
                'incident_location'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('complaints', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
