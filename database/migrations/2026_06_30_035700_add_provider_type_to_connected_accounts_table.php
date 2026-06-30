<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->string('provider_type')->default('google_drive')->after('user_id');
            $table->foreignId('workspace_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->string('account_name')->nullable()->after('google_email');
            $table->json('credentials')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('health_status')->default('unknown');
        });
    }

    public function down(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn(['provider_type', 'workspace_id', 'account_name', 'credentials', 'last_health_check_at', 'health_status']);
        });
    }
};
