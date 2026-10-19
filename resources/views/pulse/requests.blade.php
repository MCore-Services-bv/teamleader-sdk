@use('McoreServices\TeamleaderSDK\Pulse\Entries')
<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Teamleader Requests"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="{{ is_array($config['threshold']) ? '' : $config['threshold'].'ms slow threshold, ' }}past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.arrows-left-right />
        </x-slot:icon>
        <x-slot:actions>
            @if (count($connections) > 1)
                <x-pulse::select
                    wire:model.live="connection"
                    id="select-teamleader-requests-connection"
                    label="Connection"
                    :options="['' => 'all'] + array_combine($connections, $connections)"
                    @change="loading = true"
                />
            @endif
            <x-pulse::select
                wire:model.live="orderBy"
                id="select-teamleader-requests-order-by"
                label="Sort by"
                :options="[
                    'count' => 'count',
                    'failures' => 'failures',
                    'slow' => 'slow',
                    'average' => 'average',
                    'slowest' => 'slowest',
                ]"
                @change="loading = true"
            />
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($requests->isEmpty())
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
                        <x-pulse::th>Endpoint</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                        <x-pulse::th class="text-right">Failed</x-pulse::th>
                        <x-pulse::th class="text-right">Slow</x-pulse::th>
                        <x-pulse::th class="text-right">Average</x-pulse::th>
                        <x-pulse::th class="text-right">Slowest</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($requests->take(100) as $request)
                        <tr wire:key="{{ $request->connection.' '.$request->endpoint }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $request->connection.' '.$request->endpoint }}-row">
                            <x-pulse::td class="max-w-[1px]">
                                <div class="teamleader-gap" title="{{ $request->connection }} · {{ $request->endpoint }}">
                                    <span class="teamleader-badge teamleader-badge-purple">{{ $request->connection }}</span>
                                    <code class="block text-xs text-gray-900 dark:text-gray-100 truncate">{{ $request->endpoint }}</code>
                                </div>
                                @if ($request->reasons !== [])
                                    <div class="teamleader-reasons">
                                        @foreach ($request->reasons as $reason => $count)
                                            <span class="teamleader-badge {{ in_array($reason, [Entries::STATE_REAUTHORIZE, Entries::STATE_NOT_CONNECTED], true) || (int) $reason >= 500 ? 'teamleader-badge-danger' : 'teamleader-badge-warning' }}">
                                                {{ Entries::reasonLabel((string) $reason) }} × {{ number_format($count) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if (is_array($config['threshold']))
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $request->threshold }}ms threshold
                                    </p>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                @if ($config['sample_rate'] < 1)
                                    <span title="Sample rate: {{ $config['sample_rate'] }}">~{{ number_format($request->count) }}</span>
                                @else
                                    {{ number_format($request->count) }}
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="{{ $request->failures > 0 ? 'teamleader-text-danger font-bold' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ number_format($request->failures) }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="{{ $request->slow > 0 ? 'teamleader-text-warning' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $config['sample_rate'] < 1 && $request->slow > 0 ? '~' : '' }}{{ number_format($request->slow) }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                @if ($request->average === null)
                                    –
                                @else
                                    <strong>{{ number_format($request->average) ?: '<1' }}</strong> ms
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                @if ($request->slowest === null)
                                    –
                                @else
                                    <strong>{{ number_format($request->slowest) ?: '<1' }}</strong> ms
                                @endif
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>

            @if ($requests->count() > 100)
                <div class="mt-2 text-xs text-gray-400 text-center">Limited to 100 entries</div>
            @endif
        @endif
    </x-pulse::scroll>
</x-pulse::card>
