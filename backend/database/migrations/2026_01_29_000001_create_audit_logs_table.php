<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->comment('Action performed: create, update, delete, view');
            $table->string('model_type')->comment('Model class name');
            $table->unsignedBigInteger('model_id')->comment('Model ID');
            $table->json('old_values')->nullable()->comment('Previous values before update');
            $table->json('new_values')->nullable()->comment('New values after update or create');
            $table->string('ip_address', 45)->nullable()->comment('IP address of the user');
            $table->text('user_agent')->nullable()->comment('Browser/device information');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
