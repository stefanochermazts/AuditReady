<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidence Upload - {{ $request->control->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8 bg-white p-8 rounded-lg shadow-md">
            <!-- Header -->
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Evidence Upload Request</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Please upload the requested evidence files below.
                </p>
            </div>

            <!-- Request Details -->
            <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Control Information</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        <strong>{{ $request->control->article_reference ?? 'Custom Control' }}:</strong> {{ $request->control->title }}
                    </p>
                    @if($request->control->description)
                        <p class="text-sm text-gray-500 mt-2">{{ $request->control->description }}</p>
                    @endif
                </div>

                @if($request->message)
                <div class="border-l-4 border-blue-500 bg-blue-50 p-4">
                    <p class="text-sm text-blue-700">{{ $request->message }}</p>
                </div>
                @endif

                <div class="text-xs text-gray-500">
                    <p><strong>Requested by:</strong> {{ $request->requestedBy->name }}</p>
                    <p><strong>Expires on:</strong> {{ $request->expires_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Error Messages -->
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Upload Form -->
            <form action="{{ route('public.evidence-request.store', ['token' => $request->public_token]) }}" 
                  method="POST" 
                  enctype="multipart/form-data"
                  class="space-y-6">
                @csrf

                <div>
                    <label for="files" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Files to Upload
                    </label>
                    <input type="file" 
                           name="files[]" 
                           id="files" 
                           multiple 
                           required
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-2 text-xs text-gray-500">
                        Allowed file types: PDF, Office documents (Word, Excel, PowerPoint), images, archives (ZIP, RAR).<br>
                        Maximum file size: 100MB per file.
                    </p>
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Upload Files
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-500 mt-6">
                <p>This is a secure upload portal. Your files will be encrypted and stored securely.</p>
                <p class="mt-2">If you have any questions, please contact the requester.</p>
            </div>
        </div>
    </div>
</body>
</html>
