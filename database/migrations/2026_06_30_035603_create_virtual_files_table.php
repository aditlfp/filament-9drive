<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('virtual_folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connected_account_id')->constrained()->cascadeOnDelete();
            $table->string('provider_file_id');
            $table->string('name');
            $table->unsignedBigInteger('size');
            $table->string('mime_type');
            $table->string('extension')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->timestamp('last_accessed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'virtual_folder_id']);
            $table->index(['workspace_id', 'name']);
            $table->index('is_favorite');
            $table->index('is_starred');
            $table->index('last_accessed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_files');
    }
};
