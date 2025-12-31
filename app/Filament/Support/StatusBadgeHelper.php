<?php

namespace App\Filament\Support;

/**
 * Helper class for semantic status badges in Enterprise Audit design system
 * 
 * Ensures accessibility by providing:
 * - Text labels for all statuses (not color-only communication)
 * - Consistent color mapping across the application
 * - WCAG AA compliant contrast ratios
 * 
 * Usage:
 * ```php
 * TextColumn::make('status')
 *     ->badge()
 *     ->formatStateUsing(fn ($state) => StatusBadgeHelper::getStatusLabel($state))
 *     ->color(fn ($state) => StatusBadgeHelper::getStatusColor($state))
 * ```
 */
class StatusBadgeHelper
{
    /**
     * Map of audit statuses to human-readable labels and colors
     * 
     * Labels are always provided to ensure accessibility (not color-only communication)
     */
    protected static array $auditStatusMap = [
        'draft' => [
            'label' => 'Draft',
            'color' => 'gray',
        ],
        'in_progress' => [
            'label' => 'In Progress',
            'color' => 'warning', // In Review
        ],
        'closed' => [
            'label' => 'Closed',
            'color' => 'success', // Completed
        ],
    ];

    /**
     * Map of evidence validation statuses to human-readable labels and colors
     */
    protected static array $validationStatusMap = [
        'pending' => [
            'label' => 'Pending',
            'color' => 'gray',
        ],
        'approved' => [
            'label' => 'Approved',
            'color' => 'success', // Completed
        ],
        'rejected' => [
            'label' => 'Rejected',
            'color' => 'danger', // Missing / Risk
        ],
        'needs_revision' => [
            'label' => 'Needs Revision',
            'color' => 'warning', // In Review
        ],
    ];

    /**
     * Map of audit types to human-readable labels and colors
     */
    protected static array $auditTypeMap = [
        'internal' => [
            'label' => 'Internal',
            'color' => 'info',
        ],
        'external' => [
            'label' => 'External',
            'color' => 'warning',
        ],
        'certification' => [
            'label' => 'Certification',
            'color' => 'success',
        ],
        'compliance' => [
            'label' => 'Compliance',
            'color' => 'danger',
        ],
    ];

    /**
     * Get human-readable label for audit status
     * 
     * Always returns a text label to ensure accessibility
     * (status meaning is clear even in grayscale / color-blind scenarios)
     */
    public static function getAuditStatusLabel(?string $status): string
    {
        if (!$status) {
            return 'Unknown';
        }

        return static::$auditStatusMap[$status]['label'] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get Filament color for audit status
     */
    public static function getAuditStatusColor(?string $status): string
    {
        if (!$status) {
            return 'gray';
        }

        return static::$auditStatusMap[$status]['color'] ?? 'gray';
    }

    /**
     * Get human-readable label for validation status
     */
    public static function getValidationStatusLabel(?string $status): string
    {
        if (!$status) {
            return 'Unknown';
        }

        return static::$validationStatusMap[$status]['label'] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get Filament color for validation status
     */
    public static function getValidationStatusColor(?string $status): string
    {
        if (!$status) {
            return 'gray';
        }

        return static::$validationStatusMap[$status]['color'] ?? 'gray';
    }

    /**
     * Get human-readable label for audit type
     */
    public static function getAuditTypeLabel(?string $type): string
    {
        if (!$type) {
            return 'Unknown';
        }

        return static::$auditTypeMap[$type]['label'] ?? ucfirst($type);
    }

    /**
     * Get Filament color for audit type
     */
    public static function getAuditTypeColor(?string $type): string
    {
        if (!$type) {
            return 'gray';
        }

        return static::$auditTypeMap[$type]['color'] ?? 'gray';
    }

    /**
     * Get semantic status label based on context
     * 
     * Maps statuses to Enterprise Audit semantic statuses:
     * - Missing / Risk (danger)
     * - In Review (warning)
     * - Completed (success)
     */
    public static function getSemanticStatusLabel(?string $status, string $context = 'audit'): string
    {
        if ($context === 'validation') {
            return static::getValidationStatusLabel($status);
        }

        return static::getAuditStatusLabel($status);
    }

    /**
     * Get semantic status color based on context
     */
    public static function getSemanticStatusColor(?string $status, string $context = 'audit'): string
    {
        if ($context === 'validation') {
            return static::getValidationStatusColor($status);
        }

        return static::getAuditStatusColor($status);
    }
}
