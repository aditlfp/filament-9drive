<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('virtual_folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('color')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'parent_id']);
            $table->index(['workspace_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_folders');
    }
};
