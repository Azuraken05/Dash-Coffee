@extends('layouts.staff')
@section('title', 'Sales Report')
@section('content')
  @php
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $fmt = fn ($n) => \App\Models\Ingredient::fmt($n);
    $preset = fn ($a, $b) => route('staff.sales', array_filter(['from' => $a->format('Y-m-d'), 'to' => $b->format('Y-m-d'), 'method' => $method]));
    $peakIdx = $count ? array_search(max($hCount), $hCount) : -1;

    // Chart datasets JSON-encoded for HTML attributes
    $dLabelsJson = json_encode(array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'), array_keys($daily)));
    $dValuesJson = json_encode(array_values($daily));
    $mLabelsJson = json_encode($months);
    $mValuesJson = json_encode(array_values($monthly));
    $hLabelsJson = json_encode(array_map(fn ($h) => \Carbon\Carbon::createFromTime($h)->format('gA'), range(0, 23)));
    $hValuesJson = json_encode(array_values($hCount));
    $hBgColorsJson = json_encode(array_map(fn ($h) => $h === $peakIdx ? '#e0a82e' : '#a8d5ba', range(0, 23)));
    $pLabelsJson = json_encode($payments->keys());
    $pValuesJson = json_encode($payments->pluck('total')->values());
  @endphp

  <!-- Data container element to safely store JSON without inline script directives -->
  <div id="chartDataContainer"
       data-d-labels="{{ $dLabelsJson }}"
       data-d-values="{{ $dValuesJson }}"
       data-m-labels="{{ $mLabelsJson }}"
       data-m-values="{{ $mValuesJson }}"
       data-h-labels="{{ $hLabelsJson }}"
       data-h-values="{{ $hValuesJson }}"
       data-h-colors="{{ $hBgColorsJson }}"
       data-p-labels="{{ $pLabelsJson }}"
       data-p-values="{{ $pValuesJson }}"
       style="display: none;"></div>

  <div class="print-head"><h2>DASH COFFEE — Sales Report</h2><p>{{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }} · {{ $method ?: 'All payment methods' }}</p></div>

  <div class="rp-head">
    <div><h1 class="pos-title">Sales Report</h1></div>
    <div class="rp-clock"><small id="clkDate"></small><strong id="clkTime"></strong></div>
  </div>

  <form class="rp-bar" method="GET" action="{{ route('staff.sales') }}">
    <label>From <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" /></label>
    <label>To <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" /></label>
    <select name="method"><option value="">All payment methods</option>@foreach ($methods as $m)<option @selected($method === $m)>{{ $m }}</option>@endforeach</select>
    <button class="btn btn-primary">Apply</button>
    <span class="presets">
      <a href="{{ $preset(now(), now()) }}">Today</a>
      <a href="{{ $preset(now()->subDays(6), now()) }}">Last 7 days</a>
      <a href="{{ $preset(now()->startOfMonth(), now()) }}">This month</a>
      <a href="{{ $preset(now()->startOfYear(), now()) }}">This year</a>
    </span>
  </form>

  <h2 class="rp-sec">Sales summary</h2>
  <div class="rp-kpis">
    <div class="rp-kpi main"><small>TOTAL SALES</small><strong>₱{{ number_format($total, 2) }}</strong></div>
    <div class="rp-kpi"><small>ORDERS</small><strong>{{ $count }}</strong></div>
    <div class="rp-kpi"><small>AVG. ORDER</small><strong>₱{{ number_format($avg, 2) }}</strong></div>
    <div class="rp-kpi"><small>ITEMS SOLD</small><strong>{{ $itemsSold }}</strong></div>
    <div class="rp-kpi"><small>PEAK HOUR</small><strong>{{ $peakLabel ?? '—' }}</strong></div>
  </div>

  <h2 class="rp-sec">Sales analytics</h2>
  <div class="rp-grid wide">
    <div class="rp-card"><h3>Daily sales</h3><div class="rp-chart"><canvas id="dChart"></canvas></div></div>
    <div class="rp-card">
      <h3>Payment methods</h3>
      @if ($payments->isEmpty())<div class="empty">No sales data yet.</div>@else
        <div class="rp-chart sm"><canvas id="pChart"></canvas></div>
        <table class="rp-table">
          <tr><th>Method</th><th class="num">Orders</th><th class="num">Total</th></tr>
          @foreach ($payments as $m => $p)<tr><td>{{ $m }}</td><td class="num">{{ $p['count'] }}</td><td class="num key">₱{{ number_format($p['total'], 2) }}</td></tr>@endforeach
        </table>
      @endif
    </div>
  </div>
  <div class="rp-grid two">
    <div class="rp-card"><h3>Monthly sales <small>{{ $to->year }}</small></h3><div class="rp-chart"><canvas id="mChart"></canvas></div></div>
    <div class="rp-card"><h3>Peak-hour analytics <small>orders per hour · gold = busiest</small></h3><div class="rp-chart"><canvas id="hChart"></canvas></div></div>
  </div>

  <h2 class="rp-sec">Products &amp; inventory</h2>
  <div class="rp-grid two">
    <div class="rp-card">
      <h3>Top-selling products</h3>
      @if ($products->isEmpty())<div class="empty">No sales data yet.</div>@else
        <div class="rp-scroll"><table class="rp-table">
          <tr><th>#</th><th>Product</th><th class="num">Qty sold</th><th class="num">Revenue</th></tr>
          @foreach ($products as $i => $p)<tr class="{{ $i === 0 ? 'top' : '' }}"><td>{{ $i + 1 }}</td><td>{{ $p->name }}</td><td class="num">{{ $p->qty }}</td><td class="num key">₱{{ number_format($p->revenue, 2) }}</td></tr>@endforeach
        </table></div>
      @endif
    </div>
    <div class="rp-card">
      <h3>Inventory alerts <small>{{ $out->count() + $low->count() }} item(s)</small></h3>
      @foreach ($out as $i)<span class="chip out">✖ {{ $i->name }} · out of stock</span>@endforeach
      @foreach ($low as $i)<span class="chip low">⚠ {{ $i->name }} · {{ $fmt($i->stock) }} {{ $i->unit }} left</span>@endforeach
      @if ($out->isEmpty() && $low->isEmpty())<div class="empty">All ingredients are well stocked.</div>@endif
    </div>
  </div>

  <h2 class="rp-sec">Transactions</h2>
  <div class="rp-card">
    <h3>Recent transactions <input class="search rp-search" id="txSearch" type="search" placeholder="Search order #, item, payment" /></h3>
    <div class="rp-scroll"><table class="rp-table" id="txTable">
      <tr><th>Order #</th><th>Date / time</th><th>Items</th><th>Payment</th><th class="num">Total</th><th></th></tr>
      @foreach ($orders->take(50) as $o)
        <tr>
          <td><strong>#{{ $o->id }}</strong></td><td>{{ $o->created_at->format('M d, g:i A') }}</td>
          <td>{{ $o->items->map(fn ($i) => $i->qty . '× ' . $i->name)->implode(', ') }}</td>
          <td>{{ $o->payment_method }}</td><td class="num key">₱{{ number_format($o->total, 2) }}</td>
          <td><a href="{{ route('staff.receipt', $o) }}">View</a></td>
        </tr>
      @endforeach
    </table></div>
    @if ($orders->isEmpty())<div class="empty">No transactions in this period.</div>@endif
    <p class="more"><a href="{{ route('staff.history') }}">View all orders &rarr;</a></p>
  </div>
  
  <div class="rp-downloads">
    <a class="btn btn-soft" href="{{ route('staff.sales.export', request()->query()) }}"> Download Excel</a>
    <button class="btn btn-primary" onclick="window.print()"> Download PDF</button>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    var colors = ['#4fb34a', '#2f6b3a', '#a8d5ba', '#8bc34a', '#c7e3b5'];
    Chart.defaults.font.family = '"Source Sans 3", sans-serif';
    Chart.defaults.color = '#3d4a40';
    var tip = { callbacks: { label: function (c) { var v = c.parsed.y !== undefined ? c.parsed.y : c.parsed; return ' ₱' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }); } } };
    var opt = { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tip }, scales: { y: { beginAtZero: true, grid: { color: '#edf0ea' } }, x: { grid: { display: false } } } };

    // Read values directly from data attributes
    var container = document.getElementById('chartDataContainer').dataset;

    new Chart(document.getElementById('dChart'), {
      type: 'line',
      options: opt,
      data: {
        labels: JSON.parse(container.dLabels),
        datasets: [{
          data: JSON.parse(container.dValues),
          borderColor: '#2f6b3a',
          backgroundColor: 'rgba(79,179,74,0.18)',
          fill: true,
          tension: 0.2,
          pointRadius: 4,
          pointBackgroundColor: '#2f6b3a'
        }]
      }
    });

    new Chart(document.getElementById('mChart'), {
      type: 'bar',
      options: opt,
      data: {
        labels: JSON.parse(container.mLabels),
        datasets: [{
          data: JSON.parse(container.mValues),
          backgroundColor: '#4fb34a',
          borderRadius: 6,
          maxBarThickness: 36
        }]
      }
    });

    new Chart(document.getElementById('hChart'), {
      type: 'bar',
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#edf0ea' } }, x: { grid: { display: false } } }
      },
      data: {
        labels: JSON.parse(container.hLabels),
        datasets: [{
          data: JSON.parse(container.hValues),
          backgroundColor: JSON.parse(container.hColors),
          borderRadius: 4
        }]
      }
    });

    var pc = document.getElementById('pChart');
    if (pc) {
      new Chart(pc, {
        type: 'doughnut',
        options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'right' }, tooltip: tip } },
        data: {
          labels: JSON.parse(container.pLabels),
          datasets: [{
            data: JSON.parse(container.pValues),
            backgroundColor: colors,
            borderWidth: 2
          }]
        }
      });
    }

    document.getElementById('txSearch').addEventListener('input', function () {
      var q = this.value.toLowerCase();
      document.querySelectorAll('#txTable tr').forEach(function (r, i) { if (i) r.hidden = r.textContent.toLowerCase().indexOf(q) < 0; });
    });
  </script>
  <script>
    function updateReportClock() {
        const now = new Date();
        const timeZone = 'Asia/Manila';

        document.getElementById('reportClockDate').textContent =
            now.toLocaleDateString('en-PH', {
                timeZone,
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

        document.getElementById('reportClockTime').textContent =
            now.toLocaleTimeString('en-PH', {
                timeZone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
    }

    updateReportClock();
    setInterval(updateReportClock, 1000);
  </script>

@endsection