@php
    $values = collect($points)->map(fn ($point) => (float) $point['value']);
    $max = max(1, (float) $values->max());
@endphp
<div class="bar-chart chart-{{ $color ?? 'green' }}">
    <div class="bar-chart-grid" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <div class="bar-chart-bars">
        @foreach ($points as $point)
            @php
                $value = (float) $point['value'];
                $height = $value > 0 ? max(6, ($value / $max) * 100) : 0;
            @endphp
            <div class="bar-col" title="{{ $point['full'] ?? $point['label'] }}: {{ \App\Support\Format::rupiah($value) }}">
                <span class="bar-value">{{ $value > 0 ? \App\Support\Format::rupiahShort($value) : '' }}</span>
                <div class="bar-track"><span class="bar-fill" style="height: {{ $height }}%"></span></div>
                <span class="bar-label">{{ $point['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>
