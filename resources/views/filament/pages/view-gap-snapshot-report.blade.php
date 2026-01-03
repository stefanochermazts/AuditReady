<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-2">{{ $this->snapshot->name }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-gray-600 dark:text-gray-400">Standard</div>
                    <div class="font-semibold">{{ $this->snapshot->standard }}</div>
                </div>
                @if($this->snapshot->audit)
                <div>
                    <div class="text-gray-600 dark:text-gray-400">Linked Audit</div>
                    <div class="font-semibold">{{ $this->snapshot->audit->name }}</div>
                </div>
                @endif
                @if($this->snapshot->completedBy)
                <div>
                    <div class="text-gray-600 dark:text-gray-400">Completed By</div>
                    <div class="font-semibold">{{ $this->snapshot->completedBy->name }}</div>
                </div>
                @endif
                @if($this->snapshot->completed_at)
                <div>
                    <div class="text-gray-600 dark:text-gray-400">Completed At</div>
                    <div class="font-semibold">{{ $this->snapshot->completed_at->format('Y-m-d H:i') }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Summary Statistics -->
        @php
            $gapAnalysis = app(\App\Services\GapSnapshotService::class)->getGapAnalysis($this->snapshot);
            $statistics = app(\App\Services\GapSnapshotService::class)->getStatistics($this->snapshot);
        @endphp

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Summary Statistics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-900 rounded">
                    <div class="text-2xl font-bold">{{ $statistics['total_controls'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Controls</div>
                </div>
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $statistics['yes_count'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Yes</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $statistics['partial_count'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Partial</div>
                </div>
                <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $statistics['no_count'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">No</div>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $statistics['gaps_count'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Gaps Identified</div>
                </div>
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $statistics['controls_without_evidence'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Without Evidence</div>
                </div>
                <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $statistics['high_risk_controls'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">High Risk</div>
                </div>
            </div>
        </div>

        <!-- Gaps by Category -->
        @if(count($gapAnalysis['gaps_by_category']) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Gaps by Category</h3>
            <div class="space-y-2">
                @foreach($gapAnalysis['gaps_by_category'] as $category => $count)
                    <div class="flex justify-between items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                        <span class="font-medium">{{ $category }}</span>
                        <span class="px-3 py-1 bg-yellow-200 dark:bg-yellow-800 rounded-full text-sm font-semibold">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- High Risk Controls -->
        @if(count($gapAnalysis['high_risk_controls_list']) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 text-red-600 dark:text-red-400">
                High Risk Controls ({{ count($gapAnalysis['high_risk_controls_list']) }})
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Controls with gaps (No/Partial) AND no evidence linked.
            </p>
            <div class="space-y-3">
                @foreach($gapAnalysis['high_risk_controls_list'] as $risk)
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded">
                        <div class="font-semibold">{{ $risk['control_reference'] ?? 'Custom Control' }}: {{ $risk['control_title'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Category: {{ $risk['category'] ?? 'Uncategorized' }} | 
                            Response: <span class="font-semibold">{{ ucfirst($risk['response']) }}</span>
                        </div>
                        @if($risk['notes'])
                            <div class="text-sm mt-2 text-gray-700 dark:text-gray-300">{{ $risk['notes'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Disclaimer -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 border-l-4 border-blue-500">
            <h3 class="text-lg font-semibold mb-2 text-blue-800 dark:text-blue-200">Important Notes</h3>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <p class="font-semibold mb-2">This report does NOT:</p>
                <ul class="list-disc list-inside mb-4 space-y-1">
                    <li>Assign scores or ratings</li>
                    <li>Declare compliance or non-compliance</li>
                    <li>Provide legal or regulatory advice</li>
                    <li>Make recommendations</li>
                </ul>
                <p>
                    <strong>This report provides:</strong> A snapshot of control responses and identified gaps for informational purposes only.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
