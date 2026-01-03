<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Audit Relationship Graph
        </x-slot>

        <x-slot name="description">
            Interactive visualization of relationships between this audit, its controls, and evidences.
        </x-slot>

        @if($this->record && $this->record->id)
            @php
                $auditId = $this->record->id;
            @endphp
            <livewire:audit-graph :audit-id="$auditId" />
        @else
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-8 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    No audit record available.
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
