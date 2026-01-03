<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Policies</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->statistics['total_policies'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Controls</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->statistics['total_controls'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Mapped Controls</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->statistics['mapped_controls'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Coverage</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->statistics['coverage_percentage'] ?? 0 }}%</div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Use the "Create Mapping" button in the header to map a policy to a control. Below you can see all existing mappings.
                </p>
            </div>
        </div>

        <!-- Existing Mappings -->
        @if($this->mappings->count() > 0)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn p-6">
                <h2 class="text-lg font-semibold mb-4">Existing Policy-Control Mappings ({{ $this->mappings->count() }})</h2>
                <div class="overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="px-3 py-3.5 text-start">
                                    <span class="group inline-flex gap-x-1.5 text-sm font-semibold text-gray-950 dark:text-white">Policy</span>
                                </th>
                                <th class="px-3 py-3.5 text-start">
                                    <span class="group inline-flex gap-x-1.5 text-sm font-semibold text-gray-950 dark:text-white">Control</span>
                                </th>
                                <th class="px-3 py-3.5 text-start">
                                    <span class="group inline-flex gap-x-1.5 text-sm font-semibold text-gray-950 dark:text-white">Coverage Notes</span>
                                </th>
                                <th class="px-3 py-3.5 text-start">
                                    <span class="group inline-flex gap-x-1.5 text-sm font-semibold text-gray-950 dark:text-white">Mapped By</span>
                                </th>
                                <th class="px-3 py-3.5 text-start">
                                    <span class="group inline-flex gap-x-1.5 text-sm font-semibold text-gray-950 dark:text-white">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @foreach($this->mappings as $mapping)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-3 py-4 text-sm">
                                    <div>
                                        <strong>{{ $mapping->policy->name }}</strong> (v{{ $mapping->policy->version }})
                                        @if($mapping->policy->hasFile())
                                            <br><span class="text-xs text-gray-500">📄 File: {{ $mapping->policy->evidence->filename ?? 'N/A' }}</span>
                                        @endif
                                        @if($mapping->policy->hasLink())
                                            <br><span class="text-xs text-gray-500">🔗 <a href="{{ $mapping->policy->internal_link }}" target="_blank" class="text-primary-600 hover:underline">Link</a></span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-sm">
                                    <div>
                                        <strong>{{ $mapping->control->article_reference ?? 'N/A' }}</strong>
                                        <br>{{ $mapping->control->title }}
                                        <br><span class="text-xs text-gray-500">{{ $mapping->control->standard }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-sm">
                                    {{ $mapping->coverage_notes ?? '—' }}
                                </td>
                                <td class="px-3 py-4 text-sm">
                                    {{ $mapping->mappedBy->name ?? 'N/A' }}
                                    <br><span class="text-xs text-gray-500">{{ $mapping->created_at->format('Y-m-d H:i') }}</span>
                                </td>
                                <td class="px-3 py-4 text-sm">
                                    <div class="flex gap-2">
                                        {{ ($this->editMappingAction)(['mappingId' => $mapping->id]) }}
                                        <x-filament::button 
                                            color="danger" 
                                            size="sm"
                                            wire:click="removeMapping({{ $mapping->id }})"
                                            wire:confirm="Are you sure you want to remove this mapping?"
                                        >
                                            Remove
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Coverage Gaps -->
        @if(($this->coverageGaps['count'] ?? 0) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Controls Without Policy ({{ $this->coverageGaps['count'] }})</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Standard</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->coverageGaps['controls_without_policy'] ?? [] as $control)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $control->standard }}</td>
                                <td class="px-4 py-3 text-sm">{{ $control->article_reference ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $control->title }}</td>
                                <td class="px-4 py-3 text-sm">{{ $control->category ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
