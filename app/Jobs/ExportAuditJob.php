<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * ExportAuditJob - Queued job for exporting audit data
 * 
 * This job handles asynchronous export of audit data to PDF or CSV format.
 * The exported file is encrypted and stored, then the user is notified via email.
 */
class ExportAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId,
        public string $format, // 'pdf' or 'csv'
        public int $userId, // User who requested the export
        public ?string $tenantId = null, // Tenant context for URL generation in queue workers
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ExportService $exportService): void
    {
        try {
            // Ensure tenant context and tenant-aware URL generation in queue workers.
            // This prevents download links being generated against the central APP_URL (e.g. localhost)
            // when the route lives under tenant subdomains (routes/tenant.php).
            $this->ensureTenantContextForUrlGeneration();

            // Find the audit
            $audit = Audit::findOrFail($this->auditId);
            $user = User::findOrFail($this->userId);

            // Export based on format
            $filePath = match ($this->format) {
                'pdf' => $exportService->exportToPdf($audit),
                'csv' => $exportService->exportToCsv($audit),
                default => throw new \InvalidArgumentException("Invalid format: {$this->format}"),
            };

            // Generate signed download URL (valid for 24 hours)
            $downloadUrl = $exportService->generateDownloadUrl($filePath);

            // Send notification email
            Mail::send('emails.export-ready', [
                'audit' => $audit,
                'format' => strtoupper($this->format),
                'downloadUrl' => $downloadUrl,
                'expiresAt' => now()->addHours(24),
            ], function ($message) use ($user, $audit) {
                $message->to($user->email, $user->name)
                    ->subject("Audit Export Ready: {$audit->name}");
            });

            Log::info("Export completed for audit {$this->auditId} in format {$this->format}", [
                'file_path' => $filePath,
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::error("Export failed for audit {$this->auditId}", [
                'format' => $this->format,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Export job failed permanently for audit {$this->auditId}", [
            'format' => $this->format,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify the user that the export failed
        try {
            $user = User::find($this->userId);
            if ($user) {
                Mail::send('emails.export-failed', [
                    'auditId' => $this->auditId,
                    'format' => strtoupper($this->format),
                ], function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                        ->subject('Audit Export Failed');
                });
            }
        } catch (\Exception $e) {
            Log::error("Failed to send export failure notification", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function ensureTenantContextForUrlGeneration(): void
    {
        if (! $this->tenantId) {
            return;
        }

        // Initialize tenancy if needed / mismatched.
        if (! tenancy()->initialized || tenant('id') !== $this->tenantId) {
            $tenant = tenancy()->central(fn () => tenancy()->find($this->tenantId));

            if ($tenant instanceof Tenant) {
                tenancy()->initialize($tenant);
            }
        }

        // Force root URL for this execution (extra safety for signed routes).
        // If the AppUrlBootstrapper already ran, this is effectively a no-op.
        try {
            $domain = tenancy()->central(fn () => Tenant::find($this->tenantId)?->domains()->first()?->domain);
            if ($domain) {
                $base = config('app.url');
                $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'http';
                $port = parse_url($base, PHP_URL_PORT);
                $baseHost = parse_url($base, PHP_URL_HOST);

                if ($baseHost && ! str_contains($domain, '.') && $domain !== $baseHost) {
                    $domain = "{$domain}.{$baseHost}";
                }

                $root = $scheme . '://' . $domain;
                if ($port && ! in_array($port, [80, 443], true)) {
                    $root .= ':' . $port;
                }

                config(['app.url' => $root]);
                URL::forceRootUrl($root);
            }
        } catch (\Throwable) {
            // Ignore if we can't resolve domain here; tenancy bootstrappers may still handle it.
        }
    }
}
