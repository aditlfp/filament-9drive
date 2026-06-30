<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('virtual_file_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('virtual_folder_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            
            $table->string('token', 64)->unique()->index();
            $table->enum('type', ['public', 'password', 'workspace_only'])->default('public');
            $table->string('password_hash')->nullable();
            
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['workspace_id', 'is_active']);
            $table->index(['expires_at', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
