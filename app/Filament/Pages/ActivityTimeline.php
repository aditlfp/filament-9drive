<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Infrastructure\Context\WorkspaceContext;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ActivityTimeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static ?string $navigationLabel = 'Activity';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.activity-timeline';

    public string $filterAction = '';
    public string $filterUser = '';

    public function getActivities()
    {
        $workspace = WorkspaceContext::getOrNull();
        if (! $workspace) {
            return collect();
        }

        return Activity::query()
            ->where('workspace_id', $workspace->id)
            ->when($this->filterAction, fn ($q) => $q->where('action', $this->filterAction))
            ->when($this->filterUser, fn ($q) => $q->where('user_id', $this->filterUser))
            ->with(['user', 'virtualFile', 'virtualFolder'])
            ->orderByDesc('created_at')
            ->paginate(50);
    }

    public function mount(): void
    {
        if (! WorkspaceContext::has()) {
            abort(403, 'No workspace available. Please create or select a workspace first.');
        }
    }

    public function getActionOptions(): array
    {
        return [
            ''               => 'All actions',
            'upload'         => 'Upload',
            'delete'         => 'Delete',
            'move'           => 'Move',
            'rename'         => 'Rename',
            'copy'           => 'Copy',
            'create_folder'  => 'Create Folder',
        ];
    }
}
