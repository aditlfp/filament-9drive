<?php

namespace App\Domain\Enums;

enum WorkspaceRole: string
{
    case Admin   = 'admin';
    case Manager = 'manager';
    case Member  = 'member';
    case Guest   = 'guest';

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /** @return Permission[] */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Manager => [
                Permission::ViewFiles,
                Permission::UploadFiles,
                Permission::RenameFiles,
                Permission::MoveFiles,
                Permission::CopyFiles,
                Permission::DeleteFiles,
                Permission::ShareFiles,
                Permission::CreateFolders,
                Permission::RenameFolders,
                Permission::MoveFolders,
                Permission::DeleteFolders,
                Permission::ManageTags,
            ],
            self::Member => [
                Permission::ViewFiles,
                Permission::UploadFiles,
                Permission::RenameFiles,
                Permission::MoveFiles,
                Permission::CopyFiles,
                Permission::DeleteFiles,
                Permission::CreateFolders,
                Permission::RenameFolders,
                Permission::ManageTags,
            ],
            self::Guest => [
                Permission::ViewFiles,
            ],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin   => 'Admin',
            self::Manager => 'Manager',
            self::Member  => 'Member',
            self::Guest   => 'Guest',
        };
    }
}
