<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Show the 2FA setup page
     */
    public function showSetupForm()
    {
        $user = Auth::user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('home')->with('info', '2FA è già abilitata.');
        }

        // Generate secret if not exists
        if (!$user->two_factor_secret) {
            $secret = $this->google2fa->generateSecretKey();
            $user->two_factor_secret = $secret;
            $user->save();
        }

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'AuditReady'),
            $user->email,
            $user->two_factor_secret
        );

        // Generate QR code as inline SVG
        $qrCodeSvg = $this->google2fa->getQRCodeInline(
            config('app.name', 'AuditReady'),
            $user->email,
            $user->two_factor_secret
        );

        return view('auth.two-factor.setup', [
            'qrCodeUrl' => $qrCodeUrl,
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => $user->two_factor_secret,
        ]);
    }

    /**
     * Enable 2FA after verification
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if (!$user->two_factor_secret) {
            return back()->withErrors(['code' => 'Secret non trovato. Ricarica la pagina.']);
        }

        // Verify the code
        $valid = $this->google2fa->verifyKey(
            $user->two_factor_secret,
            $request->input('code')
        );

        if (!$valid) {
            return back()->withErrors(['code' => 'Il codice inserito non è valido.']);
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->setRecoveryCodes($recoveryCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Mark 2FA as verified in session
        session(['2fa_verified' => true]);

        return redirect()->route('2fa.recovery-codes')
            ->with('recoveryCodes', $recoveryCodes);
    }

    /**
     * Show recovery codes
     */
    public function showRecoveryCodes()
    {
        $recoveryCodes = session('recoveryCodes');

        if (!$recoveryCodes) {
            return redirect()->route('home');
        }

        return view('auth.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Show 2FA verification form (during login)
     */
    public function showVerificationForm()
    {
        if (!session('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor.verify');
    }

    /**
     * Verify 2FA code during login
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('login.id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = Auth::getProvider()->retrieveById($userId);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login');
        }

        // First check if it's a recovery code
        $recoveryCodes = $user->getRecoveryCodes();
        $code = $request->input('code');
        $isRecoveryCode = in_array($code, $recoveryCodes);
        
        if ($isRecoveryCode) {
            // Remove used recovery code
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
            $user->setRecoveryCodes($recoveryCodes);
            $user->save();
            $valid = true;
        } else {
            // Verify the TOTP code
            $valid = $this->google2fa->verifyKey(
                $user->two_factor_secret,
                $code
            );
        }

        if (!$valid) {
            return back()->withErrors(['code' => 'Il codice inserito non è valido. Assicurati di usare il codice più recente da Microsoft Authenticator o un recovery code valido.']);
        }

        if (!$valid) {
            return back()->withErrors(['code' => 'Il codice inserito non è valido.']);
        }

        // Mark 2FA as verified
        session(['2fa_verified' => true]);

        // Continue with login
        Auth::loginUsingId($userId, session('login.remember', false));
        
        // Clear login session
        session()->forget(['login.id', 'login.remember']);

        return redirect()->intended('/');
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (!Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $request->input('password'),
        ])) {
            return back()->withErrors(['password' => 'Password non corretta.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        session()->forget('2fa_verified');

        return redirect()->route('home')->with('success', '2FA disabilitata con successo.');
    }

    /**
     * Generate recovery codes
     */
    protected function generateRecoveryCodes(): array
    {
        return Collection::times(8, function () {
            return Str::random(10);
        })->all();
    }
}
