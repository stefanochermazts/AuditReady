<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configura 2FA - {{ config('app.name', 'AuditReady') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white dark:bg-gray-800 p-8 rounded-lg shadow">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                    Configura Autenticazione a Due Fattori
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    Scansiona il QR code con Microsoft Authenticator o un'app compatibile
                </p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="mt-8 space-y-6" action="{{ route('2fa.enable') }}" method="POST">
                @csrf

                <div class="text-center">
                    <div class="inline-block p-4 bg-white dark:bg-gray-700 rounded-lg">
                        {!! $qrCodeSvg !!}
                    </div>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        <strong>Codice segreto:</strong> {{ $secret }}
                    </p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                        (Usa questo codice se non puoi scansionare il QR code)
                    </p>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 rounded">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                        Istruzioni per Microsoft Authenticator:
                    </h3>
                    <ol class="list-decimal list-inside text-sm text-blue-700 dark:text-blue-300 space-y-1">
                        <li>Apri Microsoft Authenticator sul tuo dispositivo</li>
                        <li>Tocca il pulsante "+" o "Aggiungi account"</li>
                        <li>Seleziona "Account personale" o "Altro"</li>
                        <li>Scansiona il QR code mostrato sopra</li>
                        <li>Inserisci il codice a 6 cifre mostrato nell'app</li>
                    </ol>
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Codice di verifica (6 cifre)
                    </label>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        required
                        maxlength="6"
                        pattern="[0-9]{6}"
                        autocomplete="one-time-code"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                        placeholder="000000"
                    />
                </div>

                <div>
                    <button
                        type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Abilita 2FA
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
