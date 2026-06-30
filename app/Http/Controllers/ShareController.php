<?php

namespace App\Http\Controllers;

use App\Application\Services\SharingService;
use App\Infrastructure\Providers\StorageProviderFactory;
use App\Models\Share;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShareController extends Controller
{
    public function __construct(
        private readonly SharingService $sharingService,
        private readonly StorageProviderFactory $providerFactory,
    ) {}

    public function show(string $token, Request $request)
    {
        $share = Share::with(['file', 'folder', 'creator'])->where('token', $token)->firstOrFail();

        if (! $share->isAccessible()) {
            abort(410, 'This share link has expired or been revoked.');
        }

        if ($share->type === 'password' && ! session("share_auth_{$share->token}")) {
            return view('share.password', compact('share'));
        }

        $this->sharingService->recordAccess($share, 'view');

        return view('share.show', compact('share'));
    }

    public function authenticate(string $token, Request $request)
    {
        $share = Share::where('token', $token)->firstOrFail();

        if (! $share->isAccessible()) {
            abort(410);
        }

        $password = $request->input('password');

        if (! $this->sharingService->validateAccess($share, $password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        session(["share_auth_{$share->token}" => true]);

        return redirect()->route('share.show', $token);
    }

    public function download(string $token, Request $request): StreamedResponse
    {
        $share = Share::with('file')->where('token', $token)->firstOrFail();

        if (! $share->isAccessible()) {
            abort(410);
        }

        if ($share->type === 'password' && ! session("share_auth_{$share->token}")) {
            abort(403);
        }

        if (! $share->file) {
            abort(404);
        }

        $this->sharingService->recordAccess($share, 'download');

        $account = $share->file->account;
        $provider = $this->providerFactory->make($account);

        $stream = $provider->download($share->file->provider_file_id);

        return response()->stream(
            function () use ($stream) {
                fpassthru($stream);
            },
            200,
            [
                'Content-Type'        => $share->file->mime_type,
                'Content-Disposition' => 'attachment; filename="' . addslashes($share->file->name) . '"',
            ]
        );
    }
}
