<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('quota_total')->nullable()->after('expires_at');
            $table->unsignedBigInteger('quota_used')->nullable()->after('quota_total');
            $table->timestamp('quota_refreshed_at')->nullable()->after('quota_used');
        });
    }

    public function down(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'quota_total',
                'quota_used',
                'quota_refreshed_at',
            ]);
        });
    }
};
