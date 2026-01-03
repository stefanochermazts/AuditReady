<?php

namespace App\Services;

use App\Models\Control;
use App\Models\ControlOwner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ControlService
{
    /**
     * Import standard controls from CSV/JSON
     * 
     * @param array $controls Array of control data
     * @param string $standard Standard name (DORA, NIS2, ISO27001)
     * @return int Number of controls imported
     */
    public function importStandardControls(array $controls, string $standard): int
    {
        $imported = 0;

        DB::transaction(function () use ($controls, $standard, &$imported) {
            foreach ($controls as $controlData) {
                // Skip if control already exists (by article_reference)
                if (isset($controlData['article_reference'])) {
                    $existing = Control::where('standard', $standard)
                        ->where('article_reference', $controlData['article_reference'])
                        ->first();
                    
                    if ($existing) {
                        continue;
                    }
                }

                Control::create([
                    'standard' => $standard,
                    'article_reference' => $controlData['article_reference'] ?? null,
                    'title' => $controlData['title'] ?? 'Untitled Control',
                    'description' => $controlData['description'] ?? null,
                    'category' => $controlData['category'] ?? null,
                ]);

                $imported++;
            }
        });

        Log::info("Imported {$imported} controls for standard: {$standard}");

        return $imported;
    }

    /**
     * Assign an owner to a control
     * 
     * @param int $controlId
     * @param int $userId
     * @param string $responsibilityLevel
     * @param string|null $roleName
     * @param string|null $notes
     * @return ControlOwner
     */
    public function assignOwner(
        int $controlId,
        int $userId,
        string $responsibilityLevel = 'primary',
        ?string $roleName = null,
        ?string $notes = null
    ): ControlOwner {
        $control = Control::findOrFail($controlId);
        $user = User::findOrFail($userId);

        // Check if assignment already exists
        $existing = ControlOwner::where('control_id', $controlId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            // Update existing assignment
            $existing->update([
                'responsibility_level' => $responsibilityLevel,
                'role_name' => $roleName,
                'notes' => $notes,
            ]);

            return $existing;
        }

        // Create new assignment
        return ControlOwner::create([
            'control_id' => $controlId,
            'user_id' => $userId,
            'responsibility_level' => $responsibilityLevel,
            'role_name' => $roleName,
            'notes' => $notes,
        ]);
    }

    /**
     * Remove an owner from a control
     * 
     * @param int $controlId
     * @param int $userId
     * @return bool
     */
    public function removeOwner(int $controlId, int $userId): bool
    {
        return ControlOwner::where('control_id', $controlId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Get ownership matrix (controls × owners)
     * 
     * @param array $filters Optional filters: standard, category, user_id, without_owners
     * @return array
     */
    public function getOwnershipMatrix(array $filters = []): array
    {
        $query = Control::with(['owners', 'controlOwners.user']);

        if (isset($filters['standard'])) {
            $query->where('standard', $filters['standard']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['user_id'])) {
            $query->whereHas('owners', function ($q) use ($filters) {
                $q->where('users.id', $filters['user_id']);
            });
        }

        if (isset($filters['without_owners']) && $filters['without_owners']) {
            $query->doesntHave('owners');
        }

        $controls = $query->get();

        $matrix = [];
        foreach ($controls as $control) {
            $owners = [];
            foreach ($control->controlOwners as $controlOwner) {
                $owners[] = [
                    'user_id' => $controlOwner->user_id,
                    'user_name' => $controlOwner->user->name,
                    'user_email' => $controlOwner->user->email,
                    'role_name' => $controlOwner->role_name,
                    'responsibility_level' => $controlOwner->responsibility_level,
                    'notes' => $controlOwner->notes,
                ];
            }

            $matrix[] = [
                'control_id' => $control->id,
                'standard' => $control->standard,
                'article_reference' => $control->article_reference,
                'title' => $control->title,
                'category' => $control->category,
                'owners' => $owners,
                'has_owners' => count($owners) > 0,
            ];
        }

        return $matrix;
    }

    /**
     * Get controls without any owners
     * 
     * @param array $filters Optional filters: standard, category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getControlsWithoutOwner(array $filters = [])
    {
        $query = Control::doesntHave('owners');

        if (isset($filters['standard'])) {
            $query->where('standard', $filters['standard']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->get();
    }

    /**
     * Get statistics about ownership
     * 
     * @param array $filters Optional filters: standard, category, without_owners
     * @return array
     */
    public function getOwnershipStatistics(array $filters = []): array
    {
        $query = Control::query();

        // Apply same filters as getOwnershipMatrix
        if (isset($filters['standard'])) {
            $query->where('standard', $filters['standard']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['without_owners']) && $filters['without_owners']) {
            $query->doesntHave('owners');
        }

        $totalControls = $query->count();
        $controlsWithOwners = (clone $query)->has('owners')->count();
        $controlsWithoutOwners = $totalControls - $controlsWithOwners;

        // For assignments, we need to count based on filtered controls
        $filteredControlIds = $query->pluck('id');
        $totalAssignments = ControlOwner::whereIn('control_id', $filteredControlIds)->count();
        $primaryOwners = ControlOwner::whereIn('control_id', $filteredControlIds)
            ->where('responsibility_level', 'primary')->count();
        $secondaryOwners = ControlOwner::whereIn('control_id', $filteredControlIds)
            ->where('responsibility_level', 'secondary')->count();
        $consultants = ControlOwner::whereIn('control_id', $filteredControlIds)
            ->where('responsibility_level', 'consultant')->count();

        return [
            'total_controls' => $totalControls,
            'controls_with_owners' => $controlsWithOwners,
            'controls_without_owners' => $controlsWithoutOwners,
            'coverage_percentage' => $totalControls > 0 
                ? round(($controlsWithOwners / $totalControls) * 100, 2) 
                : 0,
            'total_assignments' => $totalAssignments,
            'primary_owners' => $primaryOwners,
            'secondary_owners' => $secondaryOwners,
            'consultants' => $consultants,
        ];
    }
}
