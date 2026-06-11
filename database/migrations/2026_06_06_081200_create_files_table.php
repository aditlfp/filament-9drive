<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_account_id')->constrained('connected_accounts')->cascadeOnDelete();
            $table->string('provider_file_id');
            $table->string('name');
            $table->unsignedBigInteger('size');
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->index(['folder_id', 'name']);
            $table->index(['storage_account_id', 'provider_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
