@extends('layouts.staff')
@section('title', 'Order History')
@section('content')
  <div class="hist">
    <div class="rp-head">
      <div><h1 class="pos-title">Order History</h1></div>
      <div class="rp-clock"><small id="clkDate"></small><strong id="clkTime"></strong></div>
    </div>

    <div class="rp-card">
      <div class="rp-scroll"><table class="rp-table">
        <tr>
          <th>Order #</th><th>Date / time</th><th>Items</th><th class="num">Total</th>
          <th>Payment</th><th>Status</th><th>Actions</th>
        </tr>
        @foreach ($orders as $o)
          <tr>
            <td><strong>#{{ $o->id }}</strong></td>
            <td>{{ $o->created_at->format('M d, Y g:i A') }}</td>
            <td>{{ $o->items->map(fn ($i) => $i->qty . '× ' . $i->name)->implode(', ') }}</td>
            <td class="num key">₱{{ number_format($o->total, 2) }}</td>
            <td>{{ $o->payment_method }}</td>
            <td><span class="badge available">{{ ucfirst($o->status) }}</span></td>
            <td class="row-actions">
              <a class="btn btn-soft btn-sm" href="{{ route('staff.receipt', $o) }}">View</a>
              <a class="btn btn-soft btn-sm" href="{{ route('staff.receipt', [$o, 'print' => 1]) }}">Print</a>
            </td>
          </tr>
        @endforeach
      </table></div>
      @if ($orders->isEmpty())<div class="empty">No orders yet.</div>@endif
      <div class="pager">{{ $orders->links('pagination::simple-default') }}</div>
    </div>
  </div>
@endsection