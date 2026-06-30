<?php

namespace App\Domain\Enums;

enum StorageProviderType: string
{
    case GOOGLE_DRIVE = 'google_drive';
    case AMAZON_S3 = 's3';
    case CLOUDFLARE_R2 = 'r2';
    case MINIO = 'minio';
    case DROPBOX = 'dropbox';
    case ONEDRIVE = 'onedrive';
    case BACKBLAZE_B2 = 'b2';
    case WEBDAV = 'webdav';
    case LOCAL = 'local';

    public function label(): string
    {
        return match ($this) {
            self::GOOGLE_DRIVE => 'Google Drive',
            self::AMAZON_S3 => 'Amazon S3',
            self::CLOUDFLARE_R2 => 'Cloudflare R2',
            self::MINIO => 'MinIO',
            self::DROPBOX => 'Dropbox',
            self::ONEDRIVE => 'OneDrive',
            self::BACKBLAZE_B2 => 'Backblaze B2',
            self::WEBDAV => 'WebDAV',
            self::LOCAL => 'Local Storage',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GOOGLE_DRIVE => 'heroicon-o-cloud',
            self::AMAZON_S3 => 'heroicon-o-cloud',
            self::CLOUDFLARE_R2 => 'heroicon-o-cloud',
            self::MINIO => 'heroicon-o-server',
            self::DROPBOX => 'heroicon-o-cloud',
            self::ONEDRIVE => 'heroicon-o-cloud',
            self::BACKBLAZE_B2 => 'heroicon-o-cloud',
            self::WEBDAV => 'heroicon-o-server',
            self::LOCAL => 'heroicon-o-server',
        };
    }
}
