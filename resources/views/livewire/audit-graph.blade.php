<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-content-ctn p-6">
        @if(empty($graphData) || empty($graphData['nodes']))
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Audit Relationship Graph
                </h3>
                <button
                    wire:click="refreshGraph"
                    class="fi-btn fi-btn-size-sm fi-color-gray fi-btn-color-gray"
                    type="button"
                >
                    <span class="fi-btn-label">Refresh</span>
                </button>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-8 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    No data available for this audit. Add controls and evidences to see the relationship graph.
                </p>
                @if(config('app.debug'))
                    <p class="text-xs text-gray-500 mt-2">
                        Debug: graphData is {{ empty($graphData) ? 'empty' : 'not empty' }}, 
                        nodes count: {{ isset($graphData['nodes']) ? count($graphData['nodes']) : 'N/A' }}
                    </p>
                @endif
            </div>
        @else
            @php
                $nodesJson = json_encode(
                    $graphData['nodes'] ?? [],
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                ) ?: '[]';
                $edgesJson = json_encode(
                    $graphData['edges'] ?? [],
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                ) ?: '[]';

                $nodesB64 = base64_encode($nodesJson);
                $edgesB64 = base64_encode($edgesJson);
            @endphp
            <div
                x-data="auditGraph"
                class="relative"
                :class="isFullscreen ? 'fixed inset-0 z-[9999] bg-white dark:bg-gray-900 p-6' : ''"
                data-nodes-b64="{{ $nodesB64 }}"
                data-edges-b64="{{ $edgesB64 }}"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Audit Relationship Graph
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            x-on:click="toggleFullscreen()"
                            class="fi-btn fi-btn-size-sm fi-color-gray fi-btn-color-gray"
                        >
                            <span class="fi-btn-label" x-text="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"></span>
                        </button>
                        <button
                            wire:click="refreshGraph"
                            class="fi-btn fi-btn-size-sm fi-color-gray fi-btn-color-gray"
                            type="button"
                        >
                            <span class="fi-btn-label">Refresh</span>
                        </button>
                    </div>
                </div>

                <div
                    x-ref="cytoscapeContainer"
                    class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800"
                    :style="isFullscreen ? 'height: calc(100vh - 200px); min-height: 400px;' : 'height: 24rem; min-height: 400px;'"
                ></div>

                <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-indigo-600"></div>
                        <span>Audit</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-green-500"></div>
                        <span>Control</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-violet-500"></div>
                        <span>Policy</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-amber-500"></div>
                        <span>Evidence</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
