<x-filament-panels::layout.base>
    <main class="fi-simple-page">
        <div class="fi-simple-page-content">
            <header class="fi-simple-header">
                <x-filament-panels::logo />

                <h1 class="fi-simple-header-heading">
                    Verifica Autenticazione a Due Fattori
                </h1>

                <p class="fi-simple-header-subheading">
                    Inserisci il codice a 6 cifre da Microsoft Authenticator
                </p>
            </header>

            @if ($errors->any())
                <div class="fi-alert fi-alert-danger">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="fi-alert fi-alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form class="space-y-4" action="{{ route('2fa.verify.post') }}" method="POST">
                @csrf

                <div>
                    <label for="code" class="audit-form-label audit-form-label-required">
                        Codice di verifica (6 cifre)
                    </label>

                    <input
                        id="code"
                        name="code"
                        type="text"
                        required
                        maxlength="6"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        autocomplete="one-time-code"
                        autofocus
                        class="audit-form-input w-full"
                        placeholder="000000"
                    />

                    <p class="audit-form-help">
                        Puoi anche usare un recovery code se hai perso accesso all'app.
                    </p>
                </div>

                <x-filament::button
                    type="submit"
                    class="w-full"
                >
                    Verifica e Accedi
                </x-filament::button>
            </form>
        </div>
    </main>
</x-filament-panels::layout.base>
