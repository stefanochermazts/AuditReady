<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FAQRCode\QRCode\Bacon;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;

class TwoFactorAuthenticationController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        // Use QRCode version with SVG backend for inline QR code generation
        $this->google2fa = new Google2FA(
            new Bacon(new SvgImageBackEnd())
        );
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
            $user->two_factor_secret,
            200, // size
            'utf-8' // encoding
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
     * Show 2FA verification form (during login or for already authenticated users)
     */
    public function showVerificationForm(Request $request)
    {
        // If user is already authenticated, allow verification
        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->hasTwoFactorEnabled()) {
                return redirect()->route('filament.admin.auth.login');
            }
            
            return view('auth.two-factor.verify');
        }
        
        // If not authenticated but we have 2fa_user_id in session or cookie, try to retrieve user
        // This happens when tenant initialization causes authentication to be lost
        // Cookie persists across tenant initialization, session might not
        // Try session first, then cookie
        $userId = session('2fa_user_id');
        if (!$userId) {
            $userId = $request->cookie('2fa_user_id');
        }
        if ($userId) {
            try {
                $user = Auth::getProvider()->retrieveById($userId);
                if ($user && $user->hasTwoFactorEnabled()) {
                    // Re-authenticate the user
                    Auth::login($user);
                    return view('auth.two-factor.verify');
                }
            } catch (\Exception $e) {
                // Ignore errors, fall through to login redirect
            }
        }

        // For login flow, require login.id
        if (!session('login.id')) {
            return redirect()->route('filament.admin.auth.login');
        }

        return view('auth.two-factor.verify');
    }

    /**
     * Verify 2FA code during login or for already authenticated users
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        // If user is already authenticated, use authenticated user
        if (Auth::check()) {
            $user = Auth::user();
            $isLoginFlow = false;
        } elseif (session('2fa_user_id') || $request->cookie('2fa_user_id')) {
            // If not authenticated but we have 2fa_user_id in session or cookie, retrieve user
            $userId = session('2fa_user_id') ?? $request->cookie('2fa_user_id');
            
            try {
                $user = Auth::getProvider()->retrieveById($userId);
                if ($user && $user->hasTwoFactorEnabled()) {
                    // Re-authenticate the user
                    Auth::login($user);
                    $isLoginFlow = false;
                } else {
                    return redirect()->route('filament.admin.auth.login');
                }
            } catch (\Exception $e) {
                return redirect()->route('filament.admin.auth.login');
            }
        } else {
            // For login flow, require login.id
            $userId = session('login.id');
            if (!$userId) {
                return redirect()->route('filament.admin.auth.login');
            }

            $user = Auth::getProvider()->retrieveById($userId);
            $isLoginFlow = true;
        }

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('filament.admin.auth.login');
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
            // The secret is automatically decrypted by the model accessor
            $valid = $this->google2fa->verifyKey(
                $user->two_factor_secret,
                $code
            );
        }

        if (!$valid) {
            return back()->withErrors(['code' => 'Il codice inserito non è valido. Assicurati di usare il codice più recente da Microsoft Authenticator o un recovery code valido.']);
        }

        // Mark 2FA as verified in both session and cookie
        // Cookie persists across tenant initialization, session might not
        session(['2fa_verified' => true]);
        
        // #region agent log
        $logPath = base_path('.cursor/debug.log');
        try {
            @file_put_contents($logPath, json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'verify-2fa',
                'hypothesisId' => 'I',
                'location' => 'TwoFactorController::verify:after-verification',
                'message' => '2FA verified, preparing redirect',
                'data' => [
                    'user_id' => $user->id,
                    'is_login_flow' => $isLoginFlow,
                    'is_authenticated' => Auth::check(),
                    'auth_user_id' => Auth::id(),
                    'session_2fa_verified' => session('2fa_verified'),
                    'session_id' => session()->getId(),
                ],
                'timestamp' => time() * 1000,
            ]) . "\n", FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // swallow logging errors
        }
        // #endregion
        
        // Clear 2fa_user_id from session and cookie as it's no longer needed
        session()->forget('2fa_user_id');

        // Cookie to remove 2fa_user_id and set 2fa_verified
        $removeCookie = cookie()->forget('2fa_user_id');
        $verifiedCookie = cookie('2fa_verified', '1', 120); // 2 hours expiry

        if ($isLoginFlow) {
            // Continue with login
            Auth::loginUsingId($user->id, session('login.remember', false));
            
            // Clear login session
            session()->forget(['login.id', 'login.remember']);

            // Redirect to Filament admin panel if coming from Filament login
            $intended = session()->pull('url.intended');
            if ($intended && str_contains($intended, '/admin')) {
                return redirect($intended)->withCookie($removeCookie)->withCookie($verifiedCookie);
            }

            return redirect()->intended('/admin')->withCookie($removeCookie)->withCookie($verifiedCookie);
        } else {
            // User is already authenticated, just redirect to admin panel
            // Use /admin path instead of route name to avoid route resolution issues
            // #region agent log
            $logPath = base_path('.cursor/debug.log');
            try {
                @file_put_contents($logPath, json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'verify-2fa',
                    'hypothesisId' => 'I',
                    'location' => 'TwoFactorController::verify:redirect-dashboard',
                    'message' => 'Redirecting to dashboard (non-login flow)',
                    'data' => [
                        'user_id' => $user->id,
                        'is_authenticated' => Auth::check(),
                        'auth_user_id' => Auth::id(),
                        'session_2fa_verified' => session('2fa_verified'),
                        'redirect_target' => '/admin',
                    ],
                    'timestamp' => time() * 1000,
                ]) . "\n", FILE_APPEND | LOCK_EX);
            } catch (\Throwable $e) {
                // swallow logging errors
            }
            // #endregion
            
            return redirect()->to('/admin')->withCookie($removeCookie)->withCookie($verifiedCookie);
        }
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
