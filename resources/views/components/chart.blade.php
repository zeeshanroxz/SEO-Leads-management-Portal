@props(['type' => 'bar', 'labels' => [], 'datasets' => [], 'xTitle' => null, 'yTitle' => null, 'unit' => '', 'stacked' => false, 'horizontal' => false, 'legend' => null, 'height' => 'h-64', 'caption' => 'Chart'])
@php
    $spec = compact('type', 'labels', 'datasets', 'xTitle', 'yTitle', 'unit', 'stacked', 'horizontal', 'legend');
@endphp
<figure {{ $attributes }}>
    <div class="{{ $height }} relative" x-data="chart(@js($spec))">
        <canvas x-ref="canvas" role="img" aria-label="{{ $caption }}"></canvas>
    </div>
    <details class="mt-2 text-xs text-slate-500">
        <summary class="cursor-pointer select-none hover:text-slate-700">View data table</summary>
        <div class="mt-2 overflow-x-auto">
            <table class="tbl tbl-compact">
                <thead><tr><th>{{ $xTitle ?: 'Category' }}</th>@foreach ($datasets as $d)<th>{{ $d['label'] ?? '' }}{{ $unit ? ' ('.$unit.')' : '' }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($labels as $i => $l)
                        <tr><td>{{ $l }}</td>@foreach ($datasets as $d)<td>{{ $d['data'][$i] ?? '' }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
