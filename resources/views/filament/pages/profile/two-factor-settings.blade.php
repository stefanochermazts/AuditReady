<x-filament-panels::page>
    <div class="space-y-6">
        @if(!$isEnabled)
            {{-- Setup 2FA --}}
            <x-filament::section>
                <x-slot name="heading">
                    Configura Two-Factor Authentication
                </x-slot>
                
                <x-slot name="description">
                    Scansiona il QR code con Microsoft Authenticator o un'app compatibile per abilitare 2FA.
                </x-slot>
                
                <div class="space-y-4">
                    <div class="flex justify-center">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            {!! $qrCodeSvg !!}
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <strong>Secret Key:</strong> 
                            <code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded font-mono text-xs">{{ $secret }}</code>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                            Usa questo codice se non puoi scansionare il QR code
                        </p>
                    </div>
                    
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Codice di verifica (6 cifre)
                        </label>
                        <input
                            id="code"
                            type="text"
                            wire:model="code"
                            wire:keydown.enter.prevent="enable"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            autocomplete="one-time-code"
                            inputmode="numeric"
                            placeholder="000000"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        @error('code')
                            <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <x-filament::button 
                        type="button" 
                        wire:click="enable"
                        color="primary"
                        class="w-full"
                    >
                        Abilita 2FA
                    </x-filament::button>
                </div>
            </x-filament::section>
        @else
            {{-- 2FA Enabled --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header-actions flex items-center gap-x-3">
                    <div class="grid gap-y-1">
                        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Two-Factor Authentication Abilitata
                        </h3>
                        <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                            La 2FA è attualmente abilitata per il tuo account.
                        </p>
                    </div>
                </div>
                
                <div class="fi-section-content-ctn divide-y divide-gray-200 dark:divide-white/10">
                    <div class="fi-section-content p-6">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 text-success-600 dark:text-success-400">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span class="font-medium">2FA Attiva</span>
                            </div>
                            
                            @if(count($recoveryCodes) > 0)
                                <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg p-4">
                                    <p class="text-sm font-medium text-warning-800 dark:text-warning-200 mb-2">
                                        Recovery Codes (salvali in un posto sicuro):
                                    </p>
                                    <div class="grid grid-cols-2 gap-2 font-mono text-xs">
                                        @foreach($recoveryCodes as $recoveryCode)
                                            <code class="bg-white dark:bg-gray-800 px-2 py-1 rounded">{{ $recoveryCode }}</code>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-warning-700 dark:text-warning-300 mt-2">
                                        Questi codici possono essere usati una sola volta per accedere se perdi l'accesso all'authenticator.
                                    </p>
                                </div>
                            @endif
                            
                            <div class="space-y-3">
                                <div>
                                    <label for="disable-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Codice TOTP o Recovery Code
                                    </label>
                                    <input
                                        id="disable-code"
                                        type="text"
                                        wire:model="code"
                                        wire:keydown.enter.prevent
                                        placeholder="Inserisci codice TOTP o recovery code per disabilitare"
                                        autocomplete="one-time-code"
                                        inputmode="numeric"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                    @error('code')
                                        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div class="flex gap-2">
                                    <x-filament::button 
                                        type="button" 
                                        wire:click="regenerateRecoveryCodes"
                                        color="warning"
                                        outlined
                                    >
                                        Rigenera Recovery Codes
                                    </x-filament::button>
                                    
                                    <x-filament::button 
                                        type="button" 
                                        wire:click="disable"
                                        color="danger"
                                        outlined
                                    >
                                        Disabilita 2FA
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Instructions --}}
        <x-filament::section>
            <x-slot name="heading">
                Istruzioni
            </x-slot>
            
            <div class="prose dark:prose-invert text-sm max-w-none">
                <h4 class="text-base font-semibold mb-2">Come configurare 2FA con Microsoft Authenticator:</h4>
                <ol class="list-decimal list-inside space-y-2 mb-4">
                    <li>Apri Microsoft Authenticator sul tuo dispositivo</li>
                    <li>Scansiona il QR code mostrato sopra</li>
                    <li>Inserisci il codice a 6 cifre generato dall'app</li>
                    <li>Salva i recovery codes in un posto sicuro</li>
                </ol>
                
                <h4 class="text-base font-semibold mb-2">Recovery Codes:</h4>
                <p class="text-sm">I recovery codes possono essere usati per accedere se perdi l'accesso all'authenticator. Ogni codice può essere usato una sola volta.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
