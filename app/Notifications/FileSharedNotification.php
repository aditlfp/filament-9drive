<?php

namespace App\Notifications;

use App\Models\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FileSharedNotification extends Notification
{
    use Queueable;

    public function __construct(public Share $share) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'file_shared',
            'resource_name' => $this->share->resourceName(),
            'share_token'   => $this->share->token,
            'share_url'     => $this->share->getPublicUrl(),
            'created_by'    => $this->share->creator?->name,
        ];
    }
}
