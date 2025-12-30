<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recovery Codes - {{ config('app.name', 'AuditReady') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white dark:bg-gray-800 p-8 rounded-lg shadow">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                    Recovery Codes
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    Salva questi codici in un posto sicuro. Li potrai usare se perdi accesso al tuo dispositivo.
                </p>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-4 rounded">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>⚠️ Importante:</strong> Questi codici vengono mostrati solo una volta. Assicurati di salvarli in un posto sicuro.
                </p>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
                <ul class="grid grid-cols-2 gap-2 font-mono text-sm">
                    @foreach ($recoveryCodes as $code)
                        <li class="p-2 bg-white dark:bg-gray-800 rounded text-center">{{ $code }}</li>
                    @endforeach
                </ul>
            </div>

            <div>
                <a
                    href="{{ route('home') }}"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Ho salvato i codici, continua
                </a>
            </div>
        </div>
    </div>
</body>
</html>
