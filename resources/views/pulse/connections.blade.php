@use('McoreServices\TeamleaderSDK\Pulse\Entries')
<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Teamleader Connections"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.signal-slash />
        </x-slot:icon>
        <x-slot:actions>
            <x-pulse::select
                wire:model.live="orderBy"
                id="select-teamleader-connections-order-by"
                label="Sort by"
                :options="[
                    'requests' => 'requests',
                    'failures' => 'failures',
                    'error_rate' => 'error rate',
                    'waits' => 'rate-limit waits',
                    'average' => 'average',
                ]"
                @change="loading = true"
            />
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($attention->isNotEmpty())
            <div class="teamleader-attention">
                @foreach ($attention as $item)
                    <div class="teamleader-attention-row" wire:key="attention-{{ $item->connection }}">
                        <div class="teamleader-gap">
                            <span class="teamleader-badge {{ $item->state === Entries::STATE_REAUTHORIZE ? 'teamleader-badge-danger' : 'teamleader-badge-warning' }}">{{ Entries::stateLabel($item->state) }}</span>
                            <code class="block text-xs text-gray-900 dark:text-gray-100 truncate" title="{{ $item->connection }}">{{ $item->connection }}</code>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $item->reason }}">
                            Since {{ $item->since->diffForHumans() }}@if ($item->reason !== '') — {{ $item->reason }}@endif
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($connections->isEmpty())
            @if ($attention->isEmpty())
                <x-pulse::no-results />
            @endif
        @else
            <x-pulse::table>
                <colgroup>
                    <col width="100%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Connection</x-pulse::th>
                        <x-pulse::th class="text-right">Requests</x-pulse::th>
                        <x-pulse::th class="text-right">Failed</x-pulse::th>
                        <x-pulse::th class="text-right">Waits</x-pulse::th>
                        <x-pulse::th class="text-right">Average</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($connections as $connection)
                        <tr wire:key="{{ $connection->connection }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $connection->connection }}-row">
                            <x-pulse::td class="max-w-[1px]">
                                <code class="block text-xs text-gray-900 dark:text-gray-100 truncate" title="{{ $connection->connection }}">{{ $connection->connection }}</code>
                                @if ($connection->refreshed > 0 || $connection->refreshFailed > 0)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Tokens renewed {{ number_format($connection->refreshed) }}×@if ($connection->refreshFailed > 0), <span class="teamleader-text-danger">{{ number_format($connection->refreshFailed) }} failed</span>@endif
                                    </p>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                {{ $config['sample_rate'] < 1 ? '~' : '' }}{{ number_format($connection->requests) }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="{{ $connection->failures > 0 ? 'teamleader-text-danger' : 'text-gray-700 dark:text-gray-300' }}">
                                <span title="{{ number_format($connection->errorRate, 1) }}% of requests">{{ number_format($connection->failures) }}</span>
                                @if ($connection->failures > 0)
                                    <p class="text-xs">{{ number_format($connection->errorRate, 1) }}%</p>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="{{ $connection->gaveUp > 0 ? 'teamleader-text-danger' : ($connection->waits > 0 ? 'teamleader-text-warning' : 'text-gray-700 dark:text-gray-300') }}">
                                <span title="{{ number_format($connection->waited / 1000, 1) }}s waited in total, longest {{ number_format($connection->longestWait) }}ms{{ $connection->gaveUp > 0 ? ', gave up '.$connection->gaveUp.'×' : '' }}">{{ number_format($connection->waits) }}</span>
                                @if ($connection->waits > 0)
                                    <p class="text-xs">{{ number_format($connection->waited / 1000, 1) }}s</p>
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                @if ($connection->average === null)
                                    –
                                @else
                                    <strong>{{ number_format($connection->average) ?: '<1' }}</strong> ms
                                @endif
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>

            @if ($total > $connections->count())
                <div class="mt-2 text-xs text-gray-400 text-center">Showing {{ $connections->count() }} of {{ $total }} connections</div>
            @endif
        @endif
    </x-pulse::scroll>
</x-pulse::card>
