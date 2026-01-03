<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidence Request Completed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-md text-center">
            <div class="text-green-600 mb-4">
                <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Request Completed</h2>
            <p class="text-gray-600">
                This evidence request has already been completed.
            </p>
            @if($request->completed_at)
                <p class="text-sm text-gray-500 mt-2">
                    Completed on: {{ $request->completed_at->format('Y-m-d H:i:s') }}
                </p>
            @endif
            <p class="text-sm text-gray-500 mt-4">
                Thank you for your submission. If you need to submit additional files, please contact the requester.
            </p>
        </div>
    </div>
</body>
</html>
