<?php

namespace Tests\Feature;

use App\Models\ConnectedAccount;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\SmartGoogleDriveUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Mockery;
use PDO;
use Tests\TestCase;

class MyFilesExplorerUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is required for database-backed feature tests.');
        }

        parent::setUp();
    }

    public function test_upload_modal_can_be_opened(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('my-files-explorer')
            ->call('openUploadModal')
            ->assertSet('showUploadModal', true)
            ->assertSee('Upload Files');
    }

    public function test_upload_files_uses_current_owned_folder(): void
    {
        $user = User::factory()->create();
        $root = Folder::rootForUser($user->id);
        $folder = Folder::query()->create([
            'user_id' => $user->id,
            'parent_id' => $root->id,
            'name' => 'Docs',
        ]);
        $account = ConnectedAccount::query()->create([
            'user_id' => $user->id,
            'google_email' => 'drive@example.com',
            'access_token' => 'token',
            'status' => 'active',
        ]);
        $upload = UploadedFile::fake()->create('report.pdf', 10, 'application/pdf');

        $this->mock(SmartGoogleDriveUploadService::class, function ($mock) use ($folder, $account): void {
            $mock->shouldReceive('uploadUploadedFile')
                ->once()
                ->with(Mockery::on(fn ($file): bool => $file instanceof UploadedFile && $file->getClientOriginalName() === 'report.pdf'), Mockery::on(fn (Folder $passedFolder): bool => $passedFolder->is($folder)))
                ->andReturnUsing(fn (UploadedFile $file): File => File::query()->create([
                    'folder_id' => $folder->id,
                    'storage_account_id' => $account->id,
                    'provider_file_id' => 'google-file-id',
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]));
        });

        Livewire::actingAs($user)
            ->test('my-files-explorer')
            ->set('folderId', $folder->id)
            ->set('uploadedFiles', [$upload])
            ->call('uploadFiles')
            ->assertSet('uploadedFiles', [])
            ->assertSet('showUploadModal', false);

        $this->assertDatabaseHas('files', [
            'folder_id' => $folder->id,
            'name' => 'report.pdf',
        ]);
    }

    public function test_upload_without_files_keeps_modal_state_and_creates_nothing(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('my-files-explorer')
            ->set('showUploadModal', true)
            ->call('uploadFiles')
            ->assertSet('showUploadModal', true);

        $this->assertDatabaseCount('files', 0);
    }

    public function test_file_selection_toggles_and_ignores_unowned_files(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $root = Folder::rootForUser($user->id);
        $otherRoot = Folder::rootForUser($otherUser->id);
        $account = ConnectedAccount::query()->create([
            'user_id' => $user->id,
            'google_email' => 'drive@example.com',
            'access_token' => 'token',
            'status' => 'active',
        ]);
        $otherAccount = ConnectedAccount::query()->create([
            'user_id' => $otherUser->id,
            'google_email' => 'other@example.com',
            'access_token' => 'token',
            'status' => 'active',
        ]);
        $file = File::query()->create([
            'folder_id' => $root->id,
            'storage_account_id' => $account->id,
            'provider_file_id' => 'owned-file',
            'name' => 'owned.txt',
            'size' => 1,
            'mime_type' => 'text/plain',
        ]);
        $otherFile = File::query()->create([
            'folder_id' => $otherRoot->id,
            'storage_account_id' => $otherAccount->id,
            'provider_file_id' => 'other-file',
            'name' => 'other.txt',
            'size' => 1,
            'mime_type' => 'text/plain',
        ]);

        Livewire::actingAs($user)
            ->test('my-files-explorer')
            ->call('toggleFileSelection', $file->id)
            ->assertSet('selectedFileIds', [$file->id])
            ->call('toggleFileSelection', $file->id)
            ->assertSet('selectedFileIds', [])
            ->call('toggleFileSelection', $otherFile->id)
            ->assertSet('selectedFileIds', []);
    }

    public function test_select_all_and_navigation_clear_selection(): void
    {
        $user = User::factory()->create();
        $root = Folder::rootForUser($user->id);
        $folder = Folder::query()->create([
            'user_id' => $user->id,
            'parent_id' => $root->id,
            'name' => 'Docs',
        ]);
        $account = ConnectedAccount::query()->create([
            'user_id' => $user->id,
            'google_email' => 'drive@example.com',
            'access_token' => 'token',
            'status' => 'active',
        ]);
        $file = File::query()->create([
            'folder_id' => $root->id,
            'storage_account_id' => $account->id,
            'provider_file_id' => 'owned-file',
            'name' => 'owned.txt',
            'size' => 1,
            'mime_type' => 'text/plain',
        ]);

        Livewire::actingAs($user)
            ->test('my-files-explorer')
            ->call('selectAllFilesInCurrentFolder')
            ->assertSet('selectedFileIds', [$file->id])
            ->call('openFolder', $folder->id)
            ->assertSet('selectedFileIds', []);
    }

    public function test_download_links_are_rendered(): void
    {
        $user = User::factory()->create();
        $root = Folder::rootForUser($user->id);
        $account = ConnectedAccount::query()->create([
            'user_id' => $user->id,
            'google_email' => 'drive@example.com',
            'access_token' => 'token',
            'status' => 'active',
        ]);
        $file = File::query()->create([
            'folder_id' => $root->id,
            'storage_account_id' => $account->id,
            'provider_file_id' => 'owned-file',
            'name' => 'owned.txt',
            'size' => 1,
            'mime_type' => 'text/plain',
        ]);

        Livewire::actingAs($user)
            ->test('my-files-explorer')
            ->assertSee($file->downloadUrl(), false)
            ->call('toggleFileSelection', $file->id)
            ->assertSee(route('file.download.bulk'), false);
    }

    public function test_upload_does_not_use_unowned_folder_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $root = Folder::rootForUser($user->id);
        $otherFolder = Folder::rootForUser($otherUser->id);
        $account = ConnectedAccount::query()->create([
            'user_id' => $user->id,
            'google_email' => 'drive@example.com',
            'access_token' => 'token',
            'status' => 'active',
        ]);
        $upload = UploadedFile::fake()->create('safe.txt', 1, 'text/plain');

        $this->mock(SmartGoogleDriveUploadService::class, function ($mock) use ($root, $account): void {
            $mock->shouldReceive('uploadUploadedFile')
                ->once()
                ->with(Mockery::type(UploadedFile::class), Mockery::on(fn (Folder $passedFolder): bool => $passedFolder->is($root)))
                ->andReturnUsing(fn (UploadedFile $file): File => File::query()->create([
                    'folder_id' => $root->id,
                    'storage_account_id' => $account->id,
                    'provider_file_id' => 'google-file-id',
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]));
        });

        Livewire::actingAs($user)
            ->test('my-files-explorer')
            ->set('folderId', $otherFolder->id)
            ->set('uploadedFiles', [$upload])
            ->call('uploadFiles');

        $this->assertDatabaseHas('files', [
            'folder_id' => $root->id,
            'name' => 'safe.txt',
        ]);
        $this->assertDatabaseMissing('files', [
            'folder_id' => $otherFolder->id,
            'name' => 'safe.txt',
        ]);
    }
}
