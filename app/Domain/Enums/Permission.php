<?php

namespace App\Domain\Enums;

enum Permission: string
{
    // File permissions
    case ViewFiles    = 'view_files';
    case UploadFiles  = 'upload_files';
    case RenameFiles  = 'rename_files';
    case MoveFiles    = 'move_files';
    case CopyFiles    = 'copy_files';
    case DeleteFiles  = 'delete_files';
    case ShareFiles   = 'share_files';

    // Folder permissions
    case CreateFolders = 'create_folders';
    case RenameFolders = 'rename_folders';
    case MoveFolders   = 'move_folders';
    case DeleteFolders = 'delete_folders';

    // Workspace management
    case ManageMembers  = 'manage_members';
    case ManageAccounts = 'manage_accounts';
    case ManagePolicies = 'manage_policies';
    case ManageTags     = 'manage_tags';
    case ManageWorkspace = 'manage_workspace';
}
