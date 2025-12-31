<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\TenantFilamentMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // Navigation density: narrower sidebar + fully collapsible sidebar on desktop.
            // This improves table real-estate while allowing users to hide navigation entirely.
            ->sidebarWidth('16rem')                 // default ~18rem; slightly tighter
            ->sidebarFullyCollapsibleOnDesktop()    // allow full collapse on desktop (hide entirely)
            ->collapsedSidebarWidth('4.25rem')      // width of the collapsed rail when using icon-only collapse elsewhere
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName('AuditReady')
            ->brandLogo(asset('images/logo.svg'))
            ->favicon(asset('favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                // Enterprise Audit Blue (Petroleum) - used sparingly for primary actions, active links, focus
                'primary' => [
                    50 => '242, 246, 250',   // #F2F6FA
                    100 => '230, 237, 244',  // #E6EDF4
                    200 => '201, 216, 234',  // #C9D8EA
                    300 => '169, 193, 222',  // #A9C1DE
                    400 => '111, 159, 200',  // #6F9FC8
                    500 => '63, 127, 179',   // #3F7FB3 - base primary
                    600 => '53, 106, 151',   // #356A97
                    700 => '45, 88, 124',    // #2D587C
                    800 => '37, 70, 98',     // #254662
                    900 => '31, 58, 82',     // #1F3A52
                    950 => '20, 38, 54',     // #142636 - darkest shade
                ],
                // Success = Completed / OK
                'success' => [
                    50 => '236, 253, 243',   // #ECFDF3
                    100 => '209, 250, 223',  // #D1FADF
                    200 => '166, 244, 197',  // #A6F4C5
                    300 => '108, 233, 166',  // #6CE9A6
                    400 => '50, 213, 131',   // #32D583
                    500 => '18, 183, 106',   // #12B76A
                    600 => '3, 152, 85',     // #039855 - base success
                    700 => '2, 122, 72',     // #027A48
                    800 => '5, 96, 58',      // #05603A
                    900 => '5, 79, 49',      // #054F31
                    950 => '3, 52, 33',      // #033421 - darkest shade
                ],
                // Warning = In Review / Attention
                'warning' => [
                    50 => '255, 250, 235',   // #FFFAEB
                    100 => '254, 240, 199',  // #FEF0C7
                    200 => '254, 223, 137',  // #FEDF89
                    300 => '254, 200, 75',   // #FEC84B
                    400 => '253, 176, 34',   // #FDB022
                    500 => '247, 144, 9',     // #F79009
                    600 => '220, 104, 3',    // #DC6803 - base warning
                    700 => '181, 71, 8',     // #B54708
                    800 => '147, 55, 13',    // #93370D
                    900 => '122, 46, 14',    // #7A2E0E
                    950 => '81, 31, 9',      // #511F09 - darkest shade
                ],
                // Danger = Missing / Risk
                'danger' => [
                    50 => '254, 243, 242',   // #FEF3F2
                    100 => '254, 228, 226',  // #FEE4E2
                    200 => '254, 205, 202',  // #FECDCA
                    300 => '253, 162, 155',  // #FDA29B
                    400 => '249, 112, 102',  // #F97066
                    500 => '240, 68, 56',    // #F04438
                    600 => '217, 45, 32',    // #D92D20 - base danger
                    700 => '180, 35, 24',    // #B42318
                    800 => '145, 32, 24',    // #912018
                    900 => '122, 39, 26',    // #7A271A
                    950 => '81, 26, 17',     // #511A11 - darkest shade
                ],
                // Neutral = Enterprise Grays (most important for backgrounds and text)
                'gray' => [
                    50 => '249, 250, 251',   // #F9FAFB - backgrounds
                    100 => '242, 244, 247',  // #F2F4F7 - backgrounds
                    200 => '234, 236, 240', // #EAECF0 - borders
                    300 => '208, 213, 221', // #D0D5DD - borders
                    400 => '152, 162, 179', // #98A2B3
                    500 => '102, 112, 133', // #667085 - text secondary
                    600 => '71, 84, 103',   // #475467 - text secondary
                    700 => '52, 64, 84',    // #344054
                    800 => '29, 41, 57',    // #1D2939 - text primary
                    900 => '16, 24, 40',    // #101828 - text primary
                    950 => '8, 12, 20',     // #080C14 - darkest shade
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                // CRITICAL: TenantFilamentMiddleware MUST be FIRST
                // It MUST run BEFORE EncryptCookies and StartSession
                // to ensure tenant context is set before session starts
                TenantFilamentMiddleware::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RequireTwoFactor::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                'Audit Management',
                'Evidence Management',
                'User Management',
                'System',
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('2FA Settings')
                    ->icon('heroicon-o-shield-check')
                    ->url(fn () => route('filament.admin.pages.two-factor-settings'))
                    ->sort(10),
            ]);
    }
}
