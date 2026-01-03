<?php

namespace App\Services;

use App\Models\Control;
use App\Models\Policy;
use App\Models\PolicyControlMapping;
use Illuminate\Support\Collection;

/**
 * PolicyService - Manages policy-control mappings and coverage analysis
 */
class PolicyService
{
    /**
     * Create a new policy
     *
     * @param array $data
     * @return Policy
     */
    public function createPolicy(array $data): Policy
    {
        // Validate that at least one of evidence_id or internal_link is provided
        if (empty($data['evidence_id']) && empty($data['internal_link'])) {
            throw new \InvalidArgumentException('Policy must have either a file (evidence_id) or an internal link');
        }

        return Policy::create($data);
    }

    /**
     * Map a policy to a control
     *
     * @param Policy $policy
     * @param Control $control
     * @param string|null $coverageNotes
     * @param int|null $mappedById
     * @return PolicyControlMapping
     */
    public function mapToControl(
        Policy $policy,
        Control $control,
        ?string $coverageNotes = null,
        ?int $mappedById = null
    ): PolicyControlMapping {
        $mappedById = $mappedById ?? auth()->id();

        // Check if mapping already exists
        $existing = PolicyControlMapping::where('policy_id', $policy->id)
            ->where('control_id', $control->id)
            ->first();

        if ($existing) {
            // Update existing mapping
            $existing->update([
                'coverage_notes' => $coverageNotes,
                'mapped_by' => $mappedById,
            ]);
            return $existing;
        }

        // Create new mapping
        return PolicyControlMapping::create([
            'policy_id' => $policy->id,
            'control_id' => $control->id,
            'coverage_notes' => $coverageNotes,
            'mapped_by' => $mappedById,
        ]);
    }

    /**
     * Remove a policy-control mapping
     *
     * @param Policy $policy
     * @param Control $control
     * @return bool
     */
    public function unmapFromControl(Policy $policy, Control $control): bool
    {
        return PolicyControlMapping::where('policy_id', $policy->id)
            ->where('control_id', $control->id)
            ->delete() > 0;
    }

    /**
     * Get controls without any policy mapping
     *
     * @param array $filters Optional filters (standard, category)
     * @return Collection
     */
    public function getControlsWithoutPolicy(array $filters = []): Collection
    {
        $query = Control::query();

        // Apply filters
        if (!empty($filters['standard'])) {
            $query->where('standard', $filters['standard']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Get controls that don't have any policy mappings
        $mappedControlIds = PolicyControlMapping::pluck('control_id')->unique();
        
        return $query->whereNotIn('id', $mappedControlIds)->get();
    }

    /**
     * Get policies without any control mapping
     *
     * @return Collection
     */
    public function getPoliciesWithoutControl(): Collection
    {
        $mappedPolicyIds = PolicyControlMapping::pluck('policy_id')->unique();
        
        return Policy::whereNotIn('id', $mappedPolicyIds)->get();
    }

    /**
     * Get coverage gaps (controls without policy)
     *
     * @param array $filters Optional filters
     * @return array
     */
    public function getCoverageGaps(array $filters = []): array
    {
        $controlsWithoutPolicy = $this->getControlsWithoutPolicy($filters);
        
        return [
            'controls_without_policy' => $controlsWithoutPolicy,
            'count' => $controlsWithoutPolicy->count(),
            'by_standard' => $controlsWithoutPolicy->groupBy('standard'),
            'by_category' => $controlsWithoutPolicy->groupBy('category'),
        ];
    }

    /**
     * Get coverage statistics
     *
     * @param array $filters Optional filters
     * @return array
     */
    public function getCoverageStatistics(array $filters = []): array
    {
        $query = Control::query();
        
        // Apply filters
        if (!empty($filters['standard'])) {
            $query->where('standard', $filters['standard']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $totalControls = $query->count();
        $mappedControlIds = PolicyControlMapping::pluck('control_id')->unique();
        $mappedControls = $query->whereIn('id', $mappedControlIds)->count();
        $unmappedControls = $totalControls - $mappedControls;

        $totalPolicies = Policy::count();
        $mappedPolicyIds = PolicyControlMapping::pluck('policy_id')->unique();
        $mappedPolicies = Policy::whereIn('id', $mappedPolicyIds)->count();
        $unmappedPolicies = $totalPolicies - $mappedPolicies;

        return [
            'total_controls' => $totalControls,
            'mapped_controls' => $mappedControls,
            'unmapped_controls' => $unmappedControls,
            'coverage_percentage' => $totalControls > 0 ? round(($mappedControls / $totalControls) * 100, 2) : 0,
            'total_policies' => $totalPolicies,
            'mapped_policies' => $mappedPolicies,
            'unmapped_policies' => $unmappedPolicies,
        ];
    }

    /**
     * Generate coverage report data
     *
     * @param array $filters Optional filters
     * @return array
     */
    public function generateCoverageReport(array $filters = []): array
    {
        $query = Control::query();
        
        // Apply filters
        if (!empty($filters['standard'])) {
            $query->where('standard', $filters['standard']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $controls = $query->with(['policies.owner', 'policies.evidence'])->get();
        
        $report = [];
        foreach ($controls as $control) {
            $policies = $control->policies;
            $report[] = [
                'control' => $control,
                'policies' => $policies,
                'policy_count' => $policies->count(),
                'mappings' => $control->policyMappings->map(function ($mapping) {
                    return [
                        'policy' => $mapping->policy,
                        'coverage_notes' => $mapping->coverage_notes,
                        'mapped_by' => $mapping->mappedBy,
                        'mapped_at' => $mapping->created_at,
                    ];
                }),
            ];
        }

        return [
            'report' => $report,
            'statistics' => $this->getCoverageStatistics($filters),
            'gaps' => $this->getCoverageGaps($filters),
        ];
    }
}
