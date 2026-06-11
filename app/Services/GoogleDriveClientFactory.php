<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Google\Client;
use Google\Service\Drive;

class GoogleDriveClientFactory
{
    public function make(ConnectedAccount $account): Drive
    {
        $secondsUntilExpiry = $account->expires_at
            ? now()->diffInSeconds($account->expires_at, false)
            : 0;

        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setScopes(config('services.google_drive.scopes', []));
        $client->setAccessType('offline');

        $client->setAccessToken([
            'access_token' => $account->access_token,
            'refresh_token' => $account->refresh_token,
            'expires_in' => max(1, $secondsUntilExpiry),
            'created' => now()->timestamp,
        ]);

        if ($client->isAccessTokenExpired() && filled($account->refresh_token)) {
            $token = $client->fetchAccessTokenWithRefreshToken($account->refresh_token);

            if (! isset($token['error'])) {
                $account->forceFill([
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? $account->refresh_token,
                    'expires_at' => isset($token['expires_in']) ? now()->addSeconds($token['expires_in']) : $account->expires_at,
                    'status' => 'active',
                ])->save();

                $client->setAccessToken([
                    'access_token' => $account->access_token,
                    'refresh_token' => $account->refresh_token,
                    'expires_in' => isset($token['expires_in']) ? (int) $token['expires_in'] : null,
                    'created' => now()->timestamp,
                ]);
            } else {
                $account->forceFill(['status' => 'expired'])->save();
            }
        } elseif ($client->isAccessTokenExpired()) {
            $account->forceFill(['status' => 'expired'])->save();
        }

        return new Drive($client);
    }
}
