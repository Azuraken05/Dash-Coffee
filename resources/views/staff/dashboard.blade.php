@extends('layouts.staff')
@section('title', 'Dashboard')
@section('content')
  @php
    $periods = ['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'year' => 'This year'];
    $avg = $count > 0 ? $sales / $count : 0;
    $outCount = $ingredients->filter(fn ($i) => (float) $i->stock <= 0)->count();
    $lowCount = $ingredients->filter(fn ($i) => (float) $i->stock > 0 && $i->isLow())->count();
    $okCount = $ingredients->count() - $outCount - $lowCount;
    $attention = $ingredients->filter(fn ($i) => $i->isLow())->sortBy(fn ($i) => [(float) $i->stock > 0 ? 1 : 0, $i->name])->values();
  @endphp
  <div class="dash-page">
    <div class="rp-head">
      <div>
        <h1 class="pos-title">Dashboard</h1>
        <p class="staff-sub">Welcome back, {{ auth()->user()->name }} · {{ $periods[$period] ?? 'Today' }}</p>
      </div>
      <div class="rp-clock"><small id="clkDate"></small><strong id="clkTime"></strong></div>
    </div>

    @if (session('ok'))
      <div class="alert-ok">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert-low"><p>{{ $errors->first() }}</p></div>
    @endif

    @include('staff._low', ['low' => $low])

    <div class="tabs">
      @foreach ($periods as $k => $label)
        <a class="tab {{ $period === $k ? 'active' : '' }}" href="{{ route('staff.dashboard', ['period' => $k]) }}">{{ $label }}</a>
      @endforeach
    </div>

    <div class="dash-stats">
      <div class="dash-stat main"><small>SALES</small><strong>₱{{ number_format($sales, 2) }}</strong></div>
      <div class="dash-stat"><small>ORDERS</small><strong>{{ $count }}</strong></div>
      <div class="dash-stat"><small>AVG. ORDER</small><strong>₱{{ number_format($avg, 2) }}</strong></div>
    </div>

    <div class="dash-cols">
      <div class="card">
        <h3>Payment split</h3>
        @foreach (['GCash', 'Maya', 'Cash', 'Bank Transfer'] as $m)
          @php $amt = $payments[$m] ?? 0; $pct = $sales > 0 ? round($amt / $sales * 100) : 0; @endphp
          <div class="split"><span>{{ $m }}</span><span>₱{{ number_format($amt, 2) }} ({{ $pct }}%)</span></div>
          <div class="bar"><div class="bar-fill" style="width: {{ $pct }}%"></div></div>
        @endforeach
      </div>

      <div class="card">
        <h3>Stock levels</h3>
        <div class="stock-boxes">
          <div class="stock-box ok"><strong>{{ $okCount }}</strong><small>In stock</small></div>
          <div class="stock-box low"><strong>{{ $lowCount }}</strong><small>Low stock</small></div>
          <div class="stock-box out"><strong>{{ $outCount }}</strong><small>No stock</small></div>
        </div>
        <div class="stock-need">
          @forelse ($attention->take(5) as $i)
            @php $out = (float) $i->stock <= 0; @endphp
            <div class="stock-item">
              <span>{{ $i->name }}</span>
              <span><strong>{{ \App\Models\Ingredient::fmt($i->stock) }} {{ $i->unit }}</strong> <em class="badge {{ $out ? 'out_of_stock' : 'low' }}">{{ $out ? 'Out' : 'Low' }}</em></span>
            </div>
          @empty
            <div class="stock-item ok-note">All ingredients are well stocked.</div>
          @endforelse
          @if ($attention->count() > 5)<div class="stock-item more-note">+ {{ $attention->count() - 5 }} more</div>@endif
        </div>
        <p class="more"><a href="{{ route('staff.inventory') }}">Manage inventory &rarr;</a></p>
      </div>
    </div>

    <div class="rp-card fit-card">
      <h3>Recent orders</h3>
      @if ($recent->isEmpty())
        <div class="empty">No orders yet</div>
      @else
        <div class="rp-scroll ord-desktop">
          <table class="rp-table fit-table mobile-card-table">
            <thead>
              <tr>
                <th scope="col">Order #</th>
                <th scope="col" class="m-hide"><span class="lbl-long">Date / time</span><span class="lbl-short">Date</span></th>
                <th scope="col">Items</th>
                <th scope="col" class="m-hide">Payment</th>
                <th scope="col" class="num">Total</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($recent as $o)
                <tr onclick="window.location.href='{{ route('staff.receipt', $o) }}'" style="cursor: pointer;">
                  <td data-label="Order"><strong>#{{ $o->id }}</strong><span class="m-sub">{{ $o->created_at->format('M d, Y') }}<br>{{ $o->created_at->format('g:i A') }}</span></td>
                  <td data-label="Date" class="m-hide"><span class="d-date">{{ $o->created_at->format('M d, Y') }}</span><span class="d-time">{{ $o->created_at->format('g:i A') }}</span></td>
                  <td data-label="Items"><span class="items-text">{{ $o->items->map(fn ($i) => $i->qty . '× ' . $i->name)->implode(', ') }}</span></td>
                  <td data-label="Payment" class="m-hide">{{ $o->payment_method }}</td>
                  <td data-label="Total" class="num key">₱{{ number_format($o->total, 2) }}</td>
                  <td data-label="Actions" class="row-actions"><a class="btn btn-soft btn-sm" href="{{ route('staff.receipt', $o) }}">View</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Phones: same card as Order History --}}
        @include('staff._order_cards', ['orders' => $recent, 'full' => true])
      @endif
      <p class="more"><a href="{{ route('staff.history') }}">View all orders &rarr;</a></p>
    </div>
  </div>

  @include('staff._delete_order_modal')

  <style>
    /* Same look as Order History / Inventory */
    .pos-main:has(.dash-page) {
      background:
        linear-gradient(rgba(198, 217, 190, 0.82), rgba(198, 217, 190, 0.82)),
        url('../assets/system-background.png') center / cover no-repeat fixed;
    }
    .pos-main:has(.dash-page) :is(.card, .rp-card, .rp-head, .dash-stat) {
      background: #fff;
      border: 1.5px solid #9fbb96;
      box-shadow: 0 8px 22px rgba(24, 36, 27, 0.16);
    }
    .pos-main:has(.dash-page) .pos-title { color: #0f1a12; }
    .pos-main:has(.dash-page) .staff-sub { color: #2b372f; font-weight: 500; }
    .pos-main:has(.dash-page) .tab { background: rgba(255, 255, 255, 0.8); color: #1c4a27; font-weight: 700; }
    .pos-main:has(.dash-page) .tab.active { background: #2f8a38; color: #fff; }
    .dash-page .card h3, .dash-page .rp-card h3 { color: #0f1a12; font-size: 1.05rem; }
    .dash-page .rp-card { margin-bottom: 18px; padding: 20px; border-radius: 20px; }

    /* Stat cards */
    .dash-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 18px; }
    .dash-stat { padding: 18px 22px; border-radius: 20px; }
    .dash-stat small { display: block; font-weight: 700; letter-spacing: 0.06em; color: #3d4a40; }
    .dash-stat strong { display: block; margin-top: 4px; font-size: 2.1rem; font-weight: 700; color: #0f1a12; }
    .pos-main:has(.dash-page) .dash-stat.main { background: #2f6b3a; border-color: #2f6b3a; }
    .dash-stat.main small { color: #cfe8c9; }
    .dash-stat.main strong { color: #fff; }

    /* Payment split + stock side by side */
    .dash-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .dash-page .bar { height: 12px; margin-bottom: 14px; background: #dfe9da; }
    .dash-page .split { font-size: 0.88rem; font-weight: 600; color: #0f1a12; }

    /* Stock card fills the row height; link sits at the bottom */
    .dash-cols > .card { display: flex; flex-direction: column; }
    .dash-cols > .card .more { margin-top: auto; padding-top: 14px; }
    .dash-need { margin-top: 16px; }
    .stock-item { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 9px 2px; border-bottom: 1px solid #dfe9da; font-size: 0.88rem; color: #0f1a12; }
    .stock-item:last-child { border-bottom: 0; }
    .stock-item em { font-style: normal; }
    .stock-item.ok-note, .stock-item.more-note { justify-content: center; color: #3d4a40; font-weight: 600; }

    /* Stock level boxes */
    .stock-boxes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; text-align: center; }
    .stock-box { padding: 16px 8px; border-radius: 14px; border: 1.5px solid; }
    .stock-box strong { display: block; font-size: 2rem; line-height: 1.1; }
    .stock-box small { font-weight: 600; color: #3d4a40; }
    .stock-box.ok { border-color: #9fbb96; background: #f1f7ee; }
    .stock-box.ok strong { color: #2f6b3a; }
    .stock-box.low { border-color: #e0a82e; background: #fff8e6; }
    .stock-box.low strong { color: #c58a00; }
    .stock-box.out { border-color: #dc6f69; background: #fff0ee; }
    .stock-box.out strong { color: #d9484e; }

    /* Recent orders table */
    .dash-page .fit-table th { background: #2f6b3a; color: #fff; border-bottom: 0; }
    .dash-page .fit-table td { color: #0f1a12; border-bottom: 1px solid #c9d9c3; font-size: 0.9rem; }
    .dash-page .fit-table tr:nth-child(even) td { background: #f1f7ee; }
    .dash-page .fit-table .row-actions { white-space: nowrap; }

    @media (max-width: 800px) {
      .dash-stats, .dash-cols { grid-template-columns: 1fr; }
    }
  </style>
@endsection
