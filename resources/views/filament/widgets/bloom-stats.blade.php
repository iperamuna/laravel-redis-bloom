<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-x-3">
            <div class="flex-1">
                <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Bloom Filter Stats ({{ $filterName }})
                </h2>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-3">
            <div class="p-4 bg-white border rounded-xl dark:bg-gray-900 dark:border-gray-700">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Hits</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $hits }}</div>
            </div>

            <div class="p-4 bg-white border rounded-xl dark:bg-gray-900 dark:border-gray-700">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Misses</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $misses }}</div>
            </div>

            <div class="p-4 bg-white border rounded-xl dark:bg-gray-900 dark:border-gray-700">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">False Positives</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $false }}</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
