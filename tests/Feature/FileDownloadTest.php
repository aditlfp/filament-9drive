<?php

namespace Tests\Feature;

use App\Models\ConnectedAccount;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\GoogleDriveDownloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class FileDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is required for database-backed feature tests.');
        }

        parent::setUp();
    }

    public function test_owner_can_download_file(): void
    {
        [$user, $file] = $this->createFileForUser();

        $this->mock(GoogleDriveDownloadService::class, function ($mock): void {
            $mock->shouldReceive('streamFile')
                ->once()
                ->andReturn(new StreamedResponse(fn () => print 'file-content', 200, [
                    'Content-Disposition' => 'attachment; filename="report.pdf"',
                ]));
        });

        $this->actingAs($user)
            ->get($file->downloadUrl())
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="report.pdf"');
    }

    public function test_other_user_cannot_download_file(): void
    {
        [, $file] = $this->createFileForUser();
        $otherUser = User::factory()->create();

        $this->mock(GoogleDriveDownloadService::class, function ($mock): void {
            $mock->shouldNotReceive('streamFile');
        });

        $this->actingAs($otherUser)
            ->get($file->downloadUrl())
            ->assertForbidden();
    }

    public function test_bulk_download_rejects_mixed_owned_and_unowned_ids(): void
    {
        [$user, $ownedFile] = $this->createFileForUser();
        [, $otherFile] = $this->createFileForUser();

        $this->mock(GoogleDriveDownloadService::class, function ($mock): void {
            $mock->shouldNotReceive('streamZip');
        });

        $this->actingAs($user)
            ->post(route('file.download.bulk'), [
                'file_ids' => [$ownedFile->id, $otherFile->id],
            ])
            ->assertForbidden();
    }

    public function test_bulk_download_streams_owned_files(): void
    {
        [$user, $firstFile] = $this->createFileForUser('first.txt');
        [, $secondFile] = $this->createFileForUser('second.txt', $user);

        $this->mock(GoogleDriveDownloadService::class, function ($mock): void {
            $mock->shouldReceive('streamZip')
                ->once()
                ->andReturn(new StreamedResponse(fn () => print 'zip-content', 200, [
                    'Content-Type' => 'application/zip',
                ]));
        });

        $this->actingAs($user)
            ->post(route('file.download.bulk'), [
                'file_ids' => [$firstFile->id, $secondFile->id],
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip');
    }

    public function test_bulk_download_requires_file_ids(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('file.download.bulk'), [
                'file_ids' => [],
            ])
            ->assertSessionHasErrors('file_ids');
    }

    /**
     * @return array{0: User, 1: File}
     */
    private function createFileForUser(string $name = 'report.pdf', ?User $user = null): array
    {
        $user ??= User::factory()->create();
        $folder = Folder::rootForUser($user->id);
        $account = ConnectedAccount::query()->create([
            'user_id' => $user->id,
            'google_email' => fake()->unique()->safeEmail(),
            'access_token' => 'token',
            'status' => 'active',
        ]);

        $file = File::query()->create([
            'folder_id' => $folder->id,
            'storage_account_id' => $account->id,
            'provider_file_id' => fake()->uuid(),
            'name' => $name,
            'size' => 12,
            'mime_type' => 'text/plain',
        ]);

        return [$user, $file];
    }
}
