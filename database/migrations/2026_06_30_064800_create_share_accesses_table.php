<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->enum('action', ['view', 'download'])->index();
            $table->timestamp('accessed_at')->index();
            
            $table->index(['share_id', 'accessed_at']);
            $table->index(['share_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_accesses');
    }
};
