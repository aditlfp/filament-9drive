<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->index(['user_id', 'updated_at']);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->index(['user_id', 'name']);
            $table->index(['user_id', 'updated_at']);
        });

        Schema::table('files', function (Blueprint $table) {
            $table->index(['name']);
            $table->index(['updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'updated_at']);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'name']);
            $table->dropIndex(['user_id', 'updated_at']);
        });

        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['updated_at']);
        });
    }
};
