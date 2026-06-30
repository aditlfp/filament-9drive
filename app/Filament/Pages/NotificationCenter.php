<?php

namespace App\Filament\Pages;

use App\Infrastructure\Context\WorkspaceContext;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class NotificationCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;
    protected static ?string $navigationLabel = 'Notifications';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.notification-center';

    public function getNotifications()
    {
        return auth()->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->dispatch('notifications-updated');
    }

    public function markRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
        $this->dispatch('notifications-updated');
    }

    public function deleteNotification(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();
    }

    public function getUnreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }
}
