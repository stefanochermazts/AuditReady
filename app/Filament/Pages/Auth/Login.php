<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Override the authenticate method to handle 2FA
     */
    protected function authenticate(): ?string
    {
        $data = $this->form->getState();

        if (!auth()->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        $user = auth()->user();

        // Check if 2FA is enabled
        if ($user->hasTwoFactorEnabled()) {
            // Store login info in session for after 2FA verification
            session(['login.id' => $user->id]);
            session(['login.remember' => $data['remember'] ?? false]);
            
            // Logout temporarily until 2FA is verified
            auth()->logout();
            
            // Redirect to 2FA verification
            return route('2fa.verify');
        }

        // No 2FA, continue normally
        session()->regenerate();

        return null;
    }
}
