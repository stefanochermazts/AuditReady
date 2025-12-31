<?php

namespace App\Filament\Pages\Profile;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FAQRCode\QRCode\Bacon;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use Illuminate\Support\Str;

class TwoFactorSettings extends Page
{
    protected string $view = 'filament.pages.profile.two-factor-settings';
    
    protected static ?string $title = 'Two-Factor Authentication';
    
    protected static bool $shouldRegisterNavigation = false; // Hide from navigation, access via profile
    
    public ?string $code = null;
    
    public ?string $secret = null;
    
    public ?string $qrCodeSvg = null;
    
    public bool $isEnabled = false;
    
    public array $recoveryCodes = [];
    
    protected function getViewData(): array
    {
        return [
            'secret' => $this->secret,
            'qrCodeSvg' => $this->qrCodeSvg,
            'isEnabled' => $this->isEnabled,
            'recoveryCodes' => $this->recoveryCodes,
        ];
    }
    
    protected Google2FA $google2fa;

    protected function initializeGoogle2fa(): void
    {
        if (isset($this->google2fa)) {
            return;
        }

        // Use QRCode version with SVG backend for inline QR code generation
        $this->google2fa = new Google2FA(
            new Bacon(new SvgImageBackEnd())
        );
    }

    public function boot(): void
    {
        // Livewire boot runs on every request (initial + updates)
        $this->initializeGoogle2fa();
    }
    
    public function mount(): void
    {
        $this->initializeGoogle2fa();
        $user = Auth::user();
        
        $this->isEnabled = $user->hasTwoFactorEnabled();
        
        if ($this->isEnabled) {
            // Show recovery codes if available
            $this->recoveryCodes = $user->getRecoveryCodes();
        } else {
            // Generate secret for setup
            if (!$user->two_factor_secret) {
                $this->secret = $this->google2fa->generateSecretKey();
                $user->two_factor_secret = $this->secret;
                $user->save();
            } else {
                $this->secret = $user->two_factor_secret;
            }
            
            // Generate QR code SVG
            $this->qrCodeSvg = $this->google2fa->getQRCodeInline(
                config('app.name', 'AuditReady'),
                $user->email,
                $this->secret,
                200, // size
                'utf-8' // encoding
            );
        }
    }
    
    public function enable(): void
    {
        $this->initializeGoogle2fa();

        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);
        
        $user = Auth::user();
        
        if (!$user->two_factor_secret) {
            $this->addError('code', 'Secret non trovato. Ricarica la pagina.');
            return;
        }
        
        // Verify the code
        $valid = $this->google2fa->verifyKey(
            $user->two_factor_secret,
            $this->code
        );
        
        if (!$valid) {
            $this->addError('code', 'Il codice inserito non è valido. Assicurati di usare il codice più recente da Microsoft Authenticator.');
            return;
        }
        
        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->setRecoveryCodes($recoveryCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();
        
        // Mark 2FA as verified in session
        session(['2fa_verified' => true]);
        
        $this->recoveryCodes = $recoveryCodes;
        $this->isEnabled = true;
        $this->code = null;
        
        $this->dispatch('2fa-enabled');
    }
    
    public function disable(): void
    {
        $this->initializeGoogle2fa();

        $this->validate([
            'code' => ['required', 'string'],
        ]);
        
        $user = Auth::user();
        
        // Check if it's a recovery code or TOTP code
        $recoveryCodes = $user->getRecoveryCodes();
        $isRecoveryCode = in_array($this->code, $recoveryCodes);
        
        if ($isRecoveryCode) {
            // Remove used recovery code
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$this->code]));
            $user->setRecoveryCodes($recoveryCodes);
        } else {
            // Verify TOTP code
            $valid = $this->google2fa->verifyKey(
                $user->two_factor_secret,
                $this->code
            );
            
            if (!$valid) {
                $this->addError('code', 'Il codice inserito non è valido. Usa un codice TOTP o un recovery code.');
                return;
            }
        }
        
        // Disable 2FA
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
        
        session()->forget('2fa_verified');
        
        $this->isEnabled = false;
        $this->code = null;
        $this->recoveryCodes = [];

        // Regenerate secret for next setup
        $this->secret = $this->google2fa->generateSecretKey();
        $user->two_factor_secret = $this->secret;
        $user->save();

        // Generate QR code SVG
        $this->qrCodeSvg = $this->google2fa->getQRCodeInline(
            config('app.name', 'AuditReady'),
            $user->email,
            $this->secret,
            200, // size
            'utf-8' // encoding
        );
        
        $this->dispatch('2fa-disabled');
    }
    
    public function regenerateRecoveryCodes(): void
    {
        $this->initializeGoogle2fa();

        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);
        
        $user = Auth::user();
        
        // Verify TOTP code
        $valid = $this->google2fa->verifyKey(
            $user->two_factor_secret,
            $this->code
        );
        
        if (!$valid) {
            $this->addError('code', 'Il codice inserito non è valido.');
            return;
        }
        
        // Generate new recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->setRecoveryCodes($recoveryCodes);
        $user->save();
        
        $this->recoveryCodes = $recoveryCodes;
        $this->code = null;
        
        $this->dispatch('recovery-codes-regenerated');
    }
    
    protected function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))->map(function () {
            return Str::random(10);
        })->all();
    }
}
