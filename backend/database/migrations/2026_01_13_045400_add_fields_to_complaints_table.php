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
        Schema::table('complaints', function (Blueprint $table) {
            $table->boolean('anonim')->default(false)->after('status');
            $table->date('incident_date')->nullable()->after('anonim');
            $table->string('incident_location', 255)->nullable()->after('incident_date');
            $table->string('perpetrator_name', 255)->nullable()->after('incident_location');
            $table->string('perpetrator_relationship', 100)->nullable()->after('perpetrator_name');
            $table->text('witnesses')->nullable()->after('perpetrator_relationship');
            $table->text('evidence_description')->nullable()->after('witnesses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn([
                'anonim',
                'incident_date',
                'incident_location',
                'perpetrator_name',
                'perpetrator_relationship',
                'witnesses',
                'evidence_description'
            ]);
        });
    }
};
