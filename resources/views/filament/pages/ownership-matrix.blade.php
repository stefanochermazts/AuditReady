<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Control Ownership Matrix</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                This matrix shows all controls and their assigned owners. Use the actions to assign or remove owners.
            </p>
            
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
