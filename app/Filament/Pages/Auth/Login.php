<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Override the authenticate method to handle 2FA
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            // Call parent authenticate (handles credentials)
            $response = parent::authenticate();
            
            // If authentication successful, check if 2FA is required
            $user = auth()->user();
            
            if ($user && $user->hasTwoFactorEnabled()) {
                // Store user ID for 2FA verification
                session(['2fa_user_id' => $user->id]);
                
                // Logout temporarily (will re-login after 2FA)
                auth()->logout();
                
                // Redirect to 2FA verification (Livewire redirect)
                $this->redirect(route('2fa.verify'));
                
                // Return null to prevent default login response
                return null;
            }
            
            return $response;
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }
}
