<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // virtual_files: most-queried columns
        Schema::table('virtual_files', function (Blueprint $table) {
            if (! $this->hasIndex('virtual_files', 'virtual_files_workspace_folder_idx')) {
                $table->index(['workspace_id', 'virtual_folder_id'], 'virtual_files_workspace_folder_idx');
            }
            if (! $this->hasIndex('virtual_files', 'virtual_files_workspace_created_idx')) {
                $table->index(['workspace_id', 'created_at'], 'virtual_files_workspace_created_idx');
            }
        });

        // activities: timeline queries
        Schema::table('activities', function (Blueprint $table) {
            if (! $this->hasIndex('activities', 'activities_workspace_created_idx')) {
                $table->index(['workspace_id', 'created_at'], 'activities_workspace_created_idx');
            }
            if (! $this->hasIndex('activities', 'activities_workspace_action_idx')) {
                $table->index(['workspace_id', 'action'], 'activities_workspace_action_idx');
            }
        });

        // connected_accounts: health/quota polling
        Schema::table('connected_accounts', function (Blueprint $table) {
            if (! $this->hasIndex('connected_accounts', 'connected_accounts_workspace_status_idx')) {
                $table->index(['workspace_id', 'status'], 'connected_accounts_workspace_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('virtual_files', function (Blueprint $table) {
            $table->dropIndex('virtual_files_workspace_folder_idx');
            $table->dropIndex('virtual_files_workspace_created_idx');
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_workspace_created_idx');
            $table->dropIndex('activities_workspace_action_idx');
        });
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->dropIndex('connected_accounts_workspace_status_idx');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(\DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($index);
    }
};
