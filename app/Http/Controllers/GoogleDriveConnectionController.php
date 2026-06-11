<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\Folder;
use App\Services\GoogleDriveQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveConnectionController extends Controller
{
    public function __construct(
        protected GoogleDriveQuotaService $quotaService,
    ) {}

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirectUrl(config('services.google_drive.redirect'))
            ->scopes(config('services.google_drive.scopes', []))
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')
            ->redirectUrl(config('services.google_drive.redirect'))
            ->user();

        $account = ConnectedAccount::query()
            ->firstOrNew([
                'user_id' => $request->user()->id,
                'google_email' => $googleUser->getEmail(),
            ]);

        $account->fill([
            'access_token' => $googleUser->token,
            'refresh_token' => $googleUser->refreshToken ?: $account->refresh_token,
            'expires_at' => $googleUser->expiresIn
                ? now()->addSeconds($googleUser->expiresIn)
                : null,
            'status' => 'active',
        ]);

        $account->save();

        Folder::rootForUser($request->user()->id);
        $this->quotaService->refresh($account);

        return redirect()
            ->route('filament.admin.resources.connected-accounts.index')
            ->with('status', 'Google Drive account connected.');
    }
}
