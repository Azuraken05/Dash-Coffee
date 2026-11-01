@extends('layouts.staff')
@section('title', 'Receipt #' . $order->id)
@section('content')
  @if ($order->trashed())
    <div class="alert-low no-print">
      <p>
        <strong>Deleted order.</strong>
        Deleted by {{ $order->deleted_by_name ?: 'Staff' }}
        on {{ $order->deleted_at?->format('M d, Y g:i A') }}.
        Reason: {{ $order->delete_reason ?: '—' }}
      </p>
    </div>
  @elseif ($order->edited_at)
    <div class="alert-ok no-print">
      Edited by {{ $order->edited_by_name ?: 'Staff' }}
      on {{ $order->edited_at->format('M d, Y g:i A') }}.
      Reason: {{ $order->edit_reason ?: '—' }}
    </div>
  @endif

  <div class="receipt-box"> <!-- Changed from .receipt to .receipt-box -->
    <h2>DASH COFFEE</h2>
    <p class="center">Eastwood Residences, Rodriguez, Rizal</p>
    <hr />
    <p>Order #{{ $order->id }}<br>{{ $order->created_at->format('M d, Y g:i A') }}<br>Cashier: {{ $order->user->name ?? '—' }}</p>
    <hr />
    @foreach ($order->items as $i)
      <div class="r-line"><span>{{ $i->name }} × {{ $i->qty }}</span><span>₱{{ number_format($i->price * $i->qty, 2) }}</span></div>
    @endforeach
    <hr />
    <div class="r-line total-line"><span>TOTAL</span><span>₱{{ number_format($order->total, 2) }}</span></div>
    @if ($order->payment_method === 'Cash')
      <div class="r-line"><span>Cash</span><span>₱{{ number_format($order->cash_received, 2) }}</span></div>
      <div class="r-line"><span>Change</span><span>₱{{ number_format($order->change_amount, 2) }}</span></div>
    @else
      <div class="r-line"><span>{{ $order->payment_method }}</span><span>Ref: {{ $order->reference ?: '—' }}</span></div>
    @endif
    <hr />
    <p class="center">Thank you! Take a dash, stay a while.</p>
  </div>
  
  <div class="receipt-actions no-print">
    <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
    <a class="btn btn-soft" href="{{ route('staff.orders') }}">New Order</a>
    <a class="btn btn-soft" href="{{ route('staff.history') }}">Order History</a>
  </div>
  
  @if (request('print'))<script>window.addEventListener('load', function () { window.print(); });</script>@endif

  <style>
    /* Screen styling */
    .receipt-box {
        max-width: 300px;
        margin: 20px auto;
        font-family: monospace;
        padding: 10px;
        background: #fff;
    }

    /* Print styling specifically for small receipt rolls */
    @media print {
        @page {
            size: 80mm 200mm;
            margin: 0;
        }
        body {
            margin: 0;
            background: #fff;
        }
        body * {
            visibility: hidden;
        }
        .receipt-box, .receipt-box * {
            visibility: visible;
        }
        .receipt-box {
            position: absolute;
            left: 0;
            top: 0;
            width: 78mm;
            margin: 0;
            padding: 2mm;
        }
    }
</style>
@endsection