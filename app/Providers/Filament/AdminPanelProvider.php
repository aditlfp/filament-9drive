<?php

namespace App\Providers\Filament;

use App\Models\User;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DriveAccountStorageWidget;
use App\Filament\Widgets\DriveStorageOverview;
use App\Filament\Widgets\WorkspaceStorageOverview;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use MWGuerra\FileManager\FileManagerPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('9Drive')
            ->brandLogo(fn () => view('filament.brand'))
            ->favicon(asset('favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugin(
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Google')
                            ->color(Color::hex('#4285F4'))
                            ->outlined(false)
                            ->scopes([
                                'openid',
                                'profile',
                                'email',
                            ]),
                    ])
                    ->slug('admin')
                    ->registration(true)
                    ->createUserUsing(function (string $provider, SocialiteUserContract $oauthUser): User {
                        return User::query()->create([
                            'name' => $oauthUser->getName() ?: Str::before($oauthUser->getEmail(), '@'),
                            'email' => $oauthUser->getEmail(),
                            'email_verified_at' => now(),
                            'password' => Str::password(32),
                        ]);
                    })
                    ->resolveUserUsing(function (string $provider, SocialiteUserContract $oauthUser): ?Authenticatable {
                        return User::query()
                            ->where('email', $oauthUser->getEmail())
                            ->first();
                    })
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                WorkspaceStorageOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
