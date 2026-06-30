<?php

namespace App\Application\Services;

use App\Domain\Contracts\EventBusInterface;
use App\Domain\Contracts\StorageProviderInterface;
use App\Domain\Events\FileDeleted;
use App\Domain\Events\FileUploaded;
use App\Domain\Events\FolderCreated;
use App\Infrastructure\Providers\StorageProviderFactory;
use App\Infrastructure\Rules\UploadRuleEngine;
use App\Models\ConnectedAccount;
use App\Models\VirtualFile;
use App\Models\VirtualFolder;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Domain\ValueObjects\UploadOptions;
use RuntimeException;

class VirtualFileSystemService
{
    public function __construct(
        private readonly StorageProviderFactory $providerFactory,
        private readonly ActivityService $activityService,
        private readonly UploadRuleEngine $ruleEngine,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function upload(
        UploadedFile $file,
        VirtualFolder $folder,
        Workspace $workspace,
    ): VirtualFile {
        $account = $this->ruleEngine->evaluate($workspace, $file);

        /** @var StorageProviderInterface $provider */
        $provider = $this->providerFactory->make($account);

        // Ensure provider folder exists at root level
        $providerFolderId = $account->provider_root_folder_id ?? null;

        $entity = $provider->upload(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            new UploadOptions(
                providerFolderId: $providerFolderId,
                mimeType: $file->getMimeType(),
            ),
        );

        return DB::transaction(function () use ($entity, $folder, $account, $workspace, $file): VirtualFile {
            $virtualFile = VirtualFile::create([
                'workspace_id' => $workspace->id,
                'virtual_folder_id' => $folder->id,
                'connected_account_id' => $account->id,
                'provider_file_id' => $entity->providerFileId,
                'name' => $entity->name,
                'size' => $entity->size,
                'mime_type' => $entity->mimeType,
            ]);

            $this->activityService->log(
                workspace: $workspace,
                action: 'upload',
                resourceType: 'file',
                resourceName: $entity->name,
                virtualFile: $virtualFile,
                account: $account,
            );

            $this->eventBus->dispatch(new FileUploaded(
                file: $virtualFile,
                workspaceId: $workspace->id,
                uploadedBy: auth()->id(),
            ));

            return $virtualFile;
        });
    }

    public function delete(VirtualFile $virtualFile): bool
    {
        $provider = $this->providerFactory->make($virtualFile->account);
        $deleted = $provider->deleteFile($virtualFile->provider_file_id);

        if ($deleted) {
            $this->activityService->log(
                workspace: $virtualFile->workspace,
                action: 'delete',
                resourceType: 'file',
                resourceName: $virtualFile->name,
                account: $virtualFile->account,
            );
            $virtualFile->delete();
        }

        return $deleted;
    }

    public function move(VirtualFile $virtualFile, VirtualFolder $targetFolder): VirtualFile
    {
        $provider = $this->providerFactory->make($virtualFile->account);
        $provider->moveFile($virtualFile->provider_file_id, $targetFolder->id);

        $virtualFile->update(['virtual_folder_id' => $targetFolder->id]);

        $this->activityService->log(
            workspace: $virtualFile->workspace,
            action: 'move',
            resourceType: 'file',
            resourceName: $virtualFile->name,
            virtualFile: $virtualFile,
        );

        return $virtualFile->fresh();
    }

    public function rename(VirtualFile $virtualFile, string $newName): VirtualFile
    {
        $provider = $this->providerFactory->make($virtualFile->account);
        $provider->renameFile($virtualFile->provider_file_id, $newName);

        $virtualFile->update(['name' => $newName]);

        $this->activityService->log(
            workspace: $virtualFile->workspace,
            action: 'rename',
            resourceType: 'file',
            resourceName: $newName,
            virtualFile: $virtualFile,
        );

        return $virtualFile->fresh();
    }

    public function copy(VirtualFile $virtualFile, VirtualFolder $targetFolder): VirtualFile
    {
        $provider = $this->providerFactory->make($virtualFile->account);
        $entity = $provider->copyFile($virtualFile->provider_file_id, $virtualFile->account->provider_root_folder_id ?? 'root');

        return DB::transaction(function () use ($entity, $virtualFile, $targetFolder): VirtualFile {
            $copy = VirtualFile::create([
                'workspace_id' => $virtualFile->workspace_id,
                'virtual_folder_id' => $targetFolder->id,
                'connected_account_id' => $virtualFile->connected_account_id,
                'provider_file_id' => $entity->providerFileId,
                'name' => $entity->name,
                'size' => $entity->size,
                'mime_type' => $entity->mimeType,
            ]);

            $this->activityService->log(
                workspace: $virtualFile->workspace,
                action: 'copy',
                resourceType: 'file',
                resourceName: $entity->name,
                virtualFile: $copy,
            );

            return $copy;
        });
    }

    public function search(Workspace $workspace, string $query): array
    {
        // Search local DB metadata — fast, provider-agnostic
        $files = VirtualFile::query()
            ->where('workspace_id', $workspace->id)
            ->where('name', 'like', "%{$query}%")
            ->with(['folder', 'account'])
            ->limit(100)
            ->get();

        return $files->toArray();
    }

    public function createFolder(Workspace $workspace, string $name, ?VirtualFolder $parent = null): VirtualFolder
    {
        $folder = VirtualFolder::create([
            'workspace_id' => $workspace->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'path' => ($parent ? $parent->path : '') . '/' . $name,
        ]);

        $this->activityService->log(
            workspace: $workspace,
            action: 'create_folder',
            resourceType: 'folder',
            resourceName: $name,
            virtualFolder: $folder,
        );

        return $folder;
    }
}
