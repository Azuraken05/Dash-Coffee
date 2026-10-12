@extends('layouts.staff')
@section('title', 'Inventory Reports')
@section('content')
    @php $keep = request()->except('type'); @endphp
    <div class="rp-head">
        <div>
            <h1 class="pos-title">Inventory Reports</h1>
            <p class="staff-sub">{{ $title }} · {{ $period }}</p>
        </div>
        <div class="rp-clock"><small id="clkDate"></small><strong id="clkTime"></strong></div>
    </div>

    <div class="tabs">
        <a class="tab" href="{{ route('staff.inventory') }}">Stock</a>
        <a class="tab active" href="{{ route('staff.inv-reports') }}">Reports</a>
    </div>

    <div class="tabs">
        @foreach ($types as $k => $label)
            <a class="tab {{ $type === $k ? 'active' : '' }}"
                href="{{ route('staff.inv-reports', array_merge($keep, ['type' => $k])) }}">{{ $label }}</a>
        @endforeach
    </div>

    <form class="rp-bar" method="GET" action="{{ route('staff.inv-reports') }}">
        <input type="hidden" name="type" value="{{ $type }}" />
        @if ($dated)
            <label>From <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" /></label>
            <label>To <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" /></label>
        @endif
        @if ($type === 'current')
            <select name="status">
                <option value="all" @selected($status === 'all')>All ingredients</option>
                <option value="low" @selected($status === 'low')>Low stock</option>
                <option value="out" @selected($status === 'out')>Out of stock</option>
            </select>
        @endif
        @if ($dated || $type === 'current')
            <button class="btn btn-primary">Apply</button>
        @endif
        @unless ($dated || $type === 'current')
            <span class="staff-sub">Shows every ingredient currently at or below its minimum.</span>
        @endunless
    </form>

    <div class="ir-sum">
        @foreach ($summary as $label => $value)
            <div><small>{{ strtoupper($label) }}</small><strong>{{ $value }}</strong></div>
        @endforeach
    </div>
    @php
        $numCols = [];
        foreach ($headings as $ci => $h) {
            $vals = collect($rows)->pluck($ci)->reject(fn($v) => $v === '—' || $v === '' || $v === null);
            $numCols[$ci] = $vals->isNotEmpty() && $vals->every(fn($v) => is_numeric($v));
        }
        $badge = ['In stock' => 'available', 'Low' => 'low', 'Out of stock' => 'out_of_stock'];
    @endphp
    <div class="rp-card">
        <div class="rp-scroll">
            <table class="rp-table rp-center">
                <tr>
                    @foreach ($headings as $ci => $h)
                        <th class="{{ $numCols[$ci] ? 'num' : '' }}">{{ $h }}</th>
                    @endforeach
                </tr>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $ci => $cell)
                            <td class="{{ $numCols[$ci] ? 'num' : '' }}">
                                @if (($headings[$ci] ?? '') === 'Status' && isset($badge[$cell]))
                                    <span
                                        class="badge {{ $badge[$cell] }}">{{ $cell }}</span>@else{{ $cell }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
        @if (count($rows) === 0)
            <div class="empty">No records for these filters.</div>
        @endif
    </div>

    <div class="rp-downloads">
        <a class="btn btn-soft" href="{{ route('staff.inv-reports.xlsx', request()->query()) }}"> Download Excel</a>
        <a class="btn btn-primary" href="{{ route('staff.inv-reports.pdf', request()->query()) }}"> Download PDF</a>
    </div>
@endsection
