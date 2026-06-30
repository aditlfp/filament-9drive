<?php

namespace App\Application\Services;

use App\Models\Activity;
use App\Models\ConnectedAccount;
use App\Models\VirtualFile;
use App\Models\VirtualFolder;
use App\Models\Workspace;

class ActivityService
{
    public function log(
        Workspace $workspace,
        string $action,
        string $resourceType,
        string $resourceName,
        ?VirtualFile $virtualFile = null,
        ?VirtualFolder $virtualFolder = null,
        ?ConnectedAccount $account = null,
    ): Activity {
        return Activity::create([
            'workspace_id' => $workspace->id,
            'user_id' => auth()->id(),
            'virtual_file_id' => $virtualFile?->id,
            'virtual_folder_id' => $virtualFolder?->id,
            'connected_account_id' => $account?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_name' => $resourceName,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
