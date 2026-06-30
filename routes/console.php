<?php

use App\Jobs\RunWorkspaceHealthChecks;
use App\Models\Workspace;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Health checks for all workspaces every hour
Schedule::call(function () {
    Workspace::pluck('id')->each(function (int $id) {
        RunWorkspaceHealthChecks::dispatch($id);
    });
})->hourly()->name('workspace-health-checks');
