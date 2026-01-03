<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header with progress -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $this->snapshot->name }}</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Standard: <span class="font-medium">{{ $this->snapshot->standard }}</span>
                    </p>
                </div>
                <div class="text-right ml-8">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Progress</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($this->getCompletionPercentage(), 1) }}%</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $this->getAnsweredControls() }} / {{ $this->getTotalControls() }} controls answered
                    </div>
                </div>
            </div>
            
            <!-- Progress bar -->
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mt-4">
                <div 
                    class="bg-primary-600 h-3 rounded-full transition-all duration-300"
                    style="width: {{ $this->getCompletionPercentage() }}%"
                ></div>
            </div>
        </div>

        <!-- Controls by category (tabs or accordion) -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                @foreach($this->steps as $step)
                    <div class="mb-8 last:mb-0">
                        <h3 class="text-lg font-semibold mb-4 pb-2 border-b">
                            {{ $step['label'] }} ({{ count($step['controls']) }} controls)
                        </h3>
                        
                        <div class="space-y-4">
                            @foreach($step['controls'] as $control)
                                @php
                                    $response = $this->responses[$control['id']] ?? null;
                                @endphp
                                
                                <div class="border rounded-lg p-4 {{ $response ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-900/20' }}">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <div class="font-semibold text-sm text-gray-600 dark:text-gray-400 mb-1">
                                                {{ $control['article_reference'] ?? 'Custom Control' }}
                                            </div>
                                            <h4 class="font-semibold text-lg">{{ $control['title'] }}</h4>
                                            @if($control['description'])
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ $control['description'] }}
                                                </p>
                                            @endif
                                        </div>
                                        @if($response)
                                            <span class="ml-4 px-2 py-1 text-xs font-semibold rounded bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                Answered
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        <!-- Response buttons -->
                                        <div class="flex flex-wrap gap-2">
                                            @php
                                                $currentResponse = $response['response'] ?? null;
                                            @endphp
                                            <button
                                                wire:click="saveResponse({{ $control['id'] }}, 'yes')"
                                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 border-2 {{ $currentResponse === 'yes' ? 'bg-green-600 text-white border-green-700 shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-green-50 dark:hover:bg-green-900/20 hover:border-green-400' }}"
                                            >
                                                ✓ Yes
                                            </button>
                                            <button
                                                wire:click="saveResponse({{ $control['id'] }}, 'partial')"
                                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 border-2 {{ $currentResponse === 'partial' ? 'bg-yellow-500 text-white border-yellow-600 shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 hover:border-yellow-400' }}"
                                            >
                                                ~ Partial
                                            </button>
                                            <button
                                                wire:click="saveResponse({{ $control['id'] }}, 'no')"
                                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 border-2 {{ $currentResponse === 'no' ? 'bg-red-600 text-white border-red-700 shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-400' }}"
                                            >
                                                ✗ No
                                            </button>
                                            <button
                                                wire:click="saveResponse({{ $control['id'] }}, 'not_applicable')"
                                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 border-2 {{ $currentResponse === 'not_applicable' ? 'bg-gray-600 text-white border-gray-700 shadow-md' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-400' }}"
                                            >
                                                N/A
                                            </button>
                                        </div>

                                        <!-- Notes field (shown when response is selected) -->
                                        @if($response)
                                            <div class="mt-3 space-y-3">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Notes (optional)
                                                    </label>
                                                    <textarea
                                                        wire:model.live.debounce.1000ms="responses.{{ $control['id'] }}.notes"
                                                        wire:change="saveNotes({{ $control['id'] }}, $event.target.value)"
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm"
                                                        rows="2"
                                                        placeholder="Add notes about this control..."
                                                    ></textarea>
                                                </div>

                                                <!-- Evidence selection -->
                                                @php
                                                    $availableEvidences = $this->getAvailableEvidences();
                                                    $linkedEvidenceIds = $response['evidence_ids'] ?? [];
                                                @endphp
                                                
                                                @if($availableEvidences->count() > 0)
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                            Link Evidence (optional)
                                                        </label>
                                                        <select
                                                            wire:model.live="responses.{{ $control['id'] }}.evidence_ids"
                                                            wire:change="saveEvidenceIds({{ $control['id'] }}, JSON.stringify(Array.from($event.target.selectedOptions, option => parseInt(option.value))))"
                                                            multiple
                                                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm"
                                                            size="3"
                                                        >
                                                            @foreach($availableEvidences as $evidence)
                                                                <option value="{{ $evidence->id }}" {{ in_array($evidence->id, $linkedEvidenceIds) ? 'selected' : '' }}>
                                                                    {{ $evidence->filename }} ({{ $evidence->category ?? 'N/A' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            Hold Ctrl/Cmd to select multiple evidences
                                                        </p>
                                                        
                                                        @if(count($linkedEvidenceIds) > 0)
                                                            <div class="mt-2">
                                                                <span class="text-xs font-semibold text-green-600 dark:text-green-400">
                                                                    {{ count($linkedEvidenceIds) }} evidence(s) linked
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @elseif($this->snapshot->audit_id)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        No evidences available in the linked audit.
                                                    </div>
                                                @else
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Link an audit to this snapshot to select evidences.
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Complete button -->
        <div class="flex justify-end">
            <button
                wire:click="completeSnapshot"
                class="px-6 py-3 bg-primary-600 text-white rounded-md font-medium hover:bg-primary-700 transition-colors"
            >
                Complete Snapshot
            </button>
        </div>
    </div>
</x-filament-panels::page>
