<?php

namespace App\Services;

use App\Models\GapSnapshot;
use App\Models\GapSnapshotResponse;
use App\Models\Control;
use App\Models\Evidence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GapSnapshotService
{
    /**
     * Create a new gap snapshot.
     *
     * @param array $data
     * @return GapSnapshot
     */
    public function createSnapshot(array $data): GapSnapshot
    {
        return GapSnapshot::create($data);
    }

    /**
     * Save or update a response for a control in a snapshot.
     *
     * @param GapSnapshot $snapshot
     * @param int $controlId
     * @param string $response
     * @param string|null $notes
     * @param array|null $evidenceIds
     * @return GapSnapshotResponse
     */
    public function saveResponse(
        GapSnapshot $snapshot,
        int $controlId,
        string $response,
        ?string $notes = null,
        ?array $evidenceIds = null
    ): GapSnapshotResponse {
        return GapSnapshotResponse::updateOrCreate(
            [
                'gap_snapshot_id' => $snapshot->id,
                'control_id' => $controlId,
            ],
            [
                'response' => $response,
                'notes' => $notes,
                'evidence_ids' => $evidenceIds,
            ]
        );
    }

    /**
     * Mark snapshot as completed.
     *
     * @param GapSnapshot $snapshot
     * @param int $userId
     * @return GapSnapshot
     */
    public function completeSnapshot(GapSnapshot $snapshot, int $userId): GapSnapshot
    {
        $snapshot->update([
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        return $snapshot->fresh();
    }

    /**
     * Get gap analysis for a snapshot.
     *
     * @param GapSnapshot $snapshot
     * @return array
     */
    public function getGapAnalysis(GapSnapshot $snapshot): array
    {
        $responses = $snapshot->responses()->with('control')->get();

        $totalControls = $this->getTotalControlsForStandard($snapshot->standard);
        $answeredControls = $responses->count();
        $unansweredControls = $totalControls - $answeredControls;

        // Group by response type
        $byResponse = [
            'yes' => $responses->where('response', 'yes')->count(),
            'no' => $responses->where('response', 'no')->count(),
            'partial' => $responses->where('response', 'partial')->count(),
            'not_applicable' => $responses->where('response', 'not_applicable')->count(),
        ];

        // Identify gaps (no or partial responses)
        $gaps = $responses->filter(function ($response) {
            return in_array($response->response, ['no', 'partial']);
        });

        // Group gaps by category
        $gapsByCategory = $gaps->groupBy(function ($response) {
            return $response->control->category ?? 'Uncategorized';
        })->map(function ($group) {
            return $group->count();
        });

        // Controls without evidence
        $controlsWithoutEvidence = $responses->filter(function ($response) {
            return !$response->hasEvidence();
        });

        // Controls with gaps and no evidence (high risk)
        $highRiskControls = $responses->filter(function ($response) {
            return $response->indicatesGap() && !$response->hasEvidence();
        });

        return [
            'total_controls' => $totalControls,
            'answered_controls' => $answeredControls,
            'unanswered_controls' => $unansweredControls,
            'completion_percentage' => $totalControls > 0 
                ? round(($answeredControls / $totalControls) * 100, 2) 
                : 0,
            'by_response' => $byResponse,
            'gaps_count' => $gaps->count(),
            'gaps_by_category' => $gapsByCategory->toArray(),
            'controls_without_evidence' => $controlsWithoutEvidence->count(),
            'high_risk_controls' => $highRiskControls->count(),
            'gaps' => $gaps->map(function ($response) {
                return [
                    'control_id' => $response->control_id,
                    'control_title' => $response->control->title,
                    'control_reference' => $response->control->article_reference,
                    'category' => $response->control->category,
                    'response' => $response->response,
                    'notes' => $response->notes,
                    'has_evidence' => $response->hasEvidence(),
                ];
            })->values()->toArray(),
            'controls_without_evidence_list' => $controlsWithoutEvidence->map(function ($response) {
                return [
                    'control_id' => $response->control_id,
                    'control_title' => $response->control->title,
                    'control_reference' => $response->control->article_reference,
                    'category' => $response->control->category,
                    'response' => $response->response,
                ];
            })->values()->toArray(),
            'high_risk_controls_list' => $highRiskControls->map(function ($response) {
                return [
                    'control_id' => $response->control_id,
                    'control_title' => $response->control->title,
                    'control_reference' => $response->control->article_reference,
                    'category' => $response->control->category,
                    'response' => $response->response,
                    'notes' => $response->notes,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get controls for a given standard.
     *
     * @param string $standard
     * @return Collection
     */
    public function getControlsForStandard(string $standard): Collection
    {
        $query = Control::query();

        if ($standard === 'DORA') {
            $query->where('standard', 'DORA');
        } elseif ($standard === 'NIS2') {
            $query->where('standard', 'NIS2');
        }
        // 'both' means all DORA and NIS2 controls

        return $query->orderBy('standard')->orderBy('article_reference')->get();
    }

    /**
     * Get total number of controls for a standard.
     *
     * @param string $standard
     * @return int
     */
    private function getTotalControlsForStandard(string $standard): int
    {
        $query = Control::query();

        if ($standard === 'DORA') {
            $query->where('standard', 'DORA');
        } elseif ($standard === 'NIS2') {
            $query->where('standard', 'NIS2');
        } else {
            // 'both' means DORA + NIS2
            $query->whereIn('standard', ['DORA', 'NIS2']);
        }

        return $query->count();
    }

    /**
     * Get statistics for a snapshot.
     *
     * @param GapSnapshot $snapshot
     * @return array
     */
    public function getStatistics(GapSnapshot $snapshot): array
    {
        $analysis = $this->getGapAnalysis($snapshot);

        return [
            'completion_percentage' => $analysis['completion_percentage'],
            'total_controls' => $analysis['total_controls'],
            'answered_controls' => $analysis['answered_controls'],
            'unanswered_controls' => $analysis['unanswered_controls'],
            'yes_count' => $analysis['by_response']['yes'],
            'no_count' => $analysis['by_response']['no'],
            'partial_count' => $analysis['by_response']['partial'],
            'not_applicable_count' => $analysis['by_response']['not_applicable'],
            'gaps_count' => $analysis['gaps_count'],
            'controls_without_evidence' => $analysis['controls_without_evidence'],
            'high_risk_controls' => $analysis['high_risk_controls'],
        ];
    }
}
