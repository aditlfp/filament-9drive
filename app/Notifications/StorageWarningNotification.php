<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StorageWarningNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $accountName,
        public float $usedPercent,
    ) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'         => 'storage_warning',
            'account_name' => $this->accountName,
            'used_percent' => $this->usedPercent,
            'message'      => "Storage account \"{$this->accountName}\" is {$this->usedPercent}% full.",
        ];
    }
}
