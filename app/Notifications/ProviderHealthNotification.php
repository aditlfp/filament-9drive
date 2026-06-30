<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProviderHealthNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $accountName,
        public string $status,
    ) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'         => 'provider_health',
            'account_name' => $this->accountName,
            'status'       => $this->status,
            'message'      => "Storage account \"{$this->accountName}\" is now {$this->status}.",
        ];
    }
}
