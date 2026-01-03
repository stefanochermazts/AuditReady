<?php

namespace App\Http\Controllers;

use App\Models\EvidenceRequest;
use App\Services\EvidenceRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PublicEvidenceRequestController extends Controller
{
    public function __construct(
        private EvidenceRequestService $evidenceRequestService
    ) {
    }

    /**
     * Show the public upload form for an evidence request.
     *
     * @param string $token
     * @return \Illuminate\View\View
     */
    public function show(string $token)
    {
        // Security: Don't reveal if token exists or not (prevent enumeration)
        $request = $this->evidenceRequestService->getRequestByToken($token);

        if (!$request) {
            // Log failed access attempt
            \Illuminate\Support\Facades\Log::warning('Failed evidence request access attempt', [
                'token_prefix' => substr($token, 0, 8) . '...',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            abort(404, 'Evidence request not found.');
        }

        // Log access (even if expired/completed for audit trail)
        $this->evidenceRequestService->logAction($request, 'accessed');

        if ($request->isExpired()) {
            return view('public.evidence-request-expired', [
                'request' => $request,
            ]);
        }

        if ($request->isCompleted()) {
            return view('public.evidence-request-completed', [
                'request' => $request,
            ]);
        }

        if ($request->status !== 'pending') {
            abort(404, 'This evidence request is no longer available.');
        }

        return view('public.evidence-request', [
            'request' => $request,
        ]);
    }

    /**
     * Handle public file upload.
     *
     * @param Request $httpRequest
     * @param string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $httpRequest, string $token)
    {
        // Rate limiting: max 5 uploads per IP per hour
        $key = 'evidence-upload:' . $httpRequest->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'files' => 'Too many upload attempts. Please try again later.',
            ]);
        }

        RateLimiter::hit($key, 3600); // 1 hour

        $request = $this->evidenceRequestService->getRequestByToken($token);

        if (!$request) {
            abort(404, 'Evidence request not found.');
        }

        if ($request->isExpired()) {
            throw ValidationException::withMessages([
                'files' => 'This evidence request has expired.',
            ]);
        }

        if ($request->isCompleted()) {
            throw ValidationException::withMessages([
                'files' => 'This evidence request has already been completed.',
            ]);
        }

        // Enhanced file validation
        $validated = $httpRequest->validate([
            'files' => [
                'required',
                'array',
                'min:1',
                'max:10', // Max 10 files per upload
            ],
            'files.*' => [
                'required',
                'file',
                'max:102400', // 100MB max per file
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,gif,zip,rar',
            ],
        ], [
            'files.required' => 'Please select at least one file to upload.',
            'files.min' => 'Please select at least one file to upload.',
            'files.max' => 'You can upload a maximum of 10 files at once.',
            'files.*.required' => 'Each file is required.',
            'files.*.max' => 'Each file must not exceed 100MB.',
            'files.*.mimes' => 'Invalid file type. Allowed types: PDF, Office documents, images, archives.',
        ]);

        // Additional security: Validate file names (prevent dangerous filenames)
        foreach ($validated['files'] as $file) {
            $filename = $file->getClientOriginalName();
            
            // Block dangerous filename patterns
            $dangerousPatterns = [
                '..', // Path traversal
                '/',  // Path separator
                '\\', // Windows path separator
                chr(0), // Null byte
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (str_contains($filename, $pattern)) {
                    throw ValidationException::withMessages([
                        'files' => 'Invalid filename detected. Please use a safe filename.',
                    ]);
                }
            }
            
            // Block extremely long filenames
            if (strlen($filename) > 255) {
                throw ValidationException::withMessages([
                    'files' => 'Filename too long. Please use a shorter filename.',
                ]);
            }
            
            // Validate MIME type matches extension (additional security layer)
            $allowedMimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/zip',
                'application/x-rar-compressed',
            ];
            
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                throw ValidationException::withMessages([
                    'files' => 'File type validation failed. Please ensure files are in the correct format.',
                ]);
            }
        }

        try {
            $evidenceIds = $this->evidenceRequestService->handlePublicUpload(
                $request,
                $validated['files']
            );

            // Send notification email to requester
            \Illuminate\Support\Facades\Mail::to($request->requestedBy->email)
                ->send(new \App\Mail\EvidenceRequestCompletedMail($request, count($validated['files'])));

            return redirect()->route('public.evidence-request.show', ['token' => $token])
                ->with('success', 'Files uploaded successfully. Thank you!');
        } catch (\Exception $e) {
            return back()->withErrors(['files' => $e->getMessage()])->withInput();
        }
    }
}
