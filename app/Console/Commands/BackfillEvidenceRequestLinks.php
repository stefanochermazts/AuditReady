<?php

namespace App\Console\Commands;

use App\Models\Evidence;
use App\Models\EvidenceRequestLog;
use Illuminate\Console\Command;

class BackfillEvidenceRequestLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-evidence-request-links {--dry-run : Do not write changes, only report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill evidences with evidence_request_id and supplier from evidence_request_logs (file_uploaded)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $logs = EvidenceRequestLog::query()
            ->where('action', 'file_uploaded')
            ->with(['evidenceRequest.supplier'])
            ->orderBy('id')
            ->get();

        $updated = 0;
        $scanned = 0;

        foreach ($logs as $log) {
            $scanned++;

            $request = $log->evidenceRequest;
            if (!$request) {
                continue;
            }

            $supplierName = $request->supplier?->name;
            $evidenceIds = $log->metadata['evidence_ids'] ?? [];

            if (!is_array($evidenceIds) || empty($evidenceIds)) {
                continue;
            }

            foreach ($evidenceIds as $evidenceId) {
                $evidence = Evidence::find($evidenceId);
                if (!$evidence) {
                    continue;
                }

                $needsUpdate = false;
                $payload = [];

                if (empty($evidence->evidence_request_id)) {
                    $payload['evidence_request_id'] = $request->id;
                    $needsUpdate = true;
                }

                if (empty($evidence->supplier) && !empty($supplierName)) {
                    $payload['supplier'] = $supplierName;
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $updated++;
                    $this->line("Evidence #{$evidence->id}: backfill " . json_encode($payload));

                    if (!$dryRun) {
                        $evidence->update($payload);
                    }
                }
            }
        }

        $this->info("Done. Logs scanned: {$scanned}. Evidences updated: {$updated}. Dry-run: " . ($dryRun ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
