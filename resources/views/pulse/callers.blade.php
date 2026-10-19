<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Teamleader Time"
        title="Per route, queued job and Artisan command: time spent on Teamleader (API calls and rate-limit waits), its share of the whole, and the calls made"
        details="where Teamleader time goes, past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.clock />
        </x-slot:icon>
        <x-slot:actions>
            <x-pulse::select
                wire:model.live="orderBy"
                id="select-teamleader-callers-order-by"
                label="Sort by"
                :options="[
                    'total' => 'total time',
                    'average' => 'average',
                    'share' => 'share',
                    'calls' => 'calls',
                    'slowest' => 'slowest',
                    'count' => 'count',
                ]"
                @change="loading = true"
            />
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($callers->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <colgroup>
                    <col width="100%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Caller</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                        <x-pulse::th class="text-right">Calls</x-pulse::th>
                        <x-pulse::th class="text-right">Average</x-pulse::th>
                        <x-pulse::th class="text-right">Share</x-pulse::th>
                        <x-pulse::th class="text-right">Slowest</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($callers->take(100) as $caller)
                        @php($id = $caller->kind.' '.$caller->name.' '.$caller->via)
                        <tr wire:key="{{ $id }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $id }}-row">
                            <x-pulse::td class="max-w-[1px]">
                                <div class="teamleader-gap" title="{{ $caller->name }}">
                                    @if ($caller->kind === 'request')
                                        @php([$method, $path] = array_pad(explode(' ', $caller->name, 2), 2, ''))
                                        <x-pulse::http-method-badge :method="$method" />
                                        <code class="block text-xs text-gray-900 dark:text-gray-100 truncate">{{ $path }}</code>
                                    @else
                                        <span class="teamleader-badge {{ $caller->kind === 'job' ? 'teamleader-badge-info' : 'teamleader-badge-neutral' }}">{{ $caller->kind }}</span>
                                        <code class="block text-xs text-gray-900 dark:text-gray-100 truncate">{{ $caller->name }}</code>
                                    @endif
                                </div>
                                @if ($caller->via)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $caller->via }}">{{ $caller->via }}</p>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                {{ $config['sample_rate'] < 1 ? '~' : '' }}{{ number_format($caller->count) }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                @if ($caller->calls === null)
                                    –
                                @else
                                    <span title="Most in one run: {{ number_format($caller->mostCalls) }}">{{ number_format($caller->calls, $caller->calls < 10 ? 1 : 0) }}</span>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                <strong>{{ number_format($caller->average) ?: '<1' }}</strong> ms
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                @if ($caller->share === null)
                                    –
                                @else
                                    <span>{{ number_format($caller->share) }}%</span>
                                    <div class="teamleader-bar"><span style="width: {{ max(2, min(100, round($caller->share))) }}%"></span></div>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                <strong>{{ number_format($caller->slowest) ?: '<1' }}</strong> ms
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>

            @if ($callers->count() > 100)
                <div class="mt-2 text-xs text-gray-400 text-center">Limited to 100 entries</div>
            @endif
        @endif
    </x-pulse::scroll>
</x-pulse::card>
