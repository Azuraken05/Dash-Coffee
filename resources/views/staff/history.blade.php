@extends('layouts.staff')
@section('title', 'Order History')
@section('content')
  <div class="hist">
    <div class="rp-head">
      <div><h1 class="pos-title">Order History</h1></div>
      <div class="rp-clock"><small id="clkDate"></small><strong id="clkTime"></strong></div>
    </div>

    @if (session('ok'))
      <div class="alert-ok">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert-low"><p>{{ $errors->first() }}</p></div>
    @endif

    <div class="rp-card">
      <div class="rp-scroll ord-desktop">
        <table class="rp-table mobile-card-table">
          <thead>
            <tr>
              <th scope="col">Order #</th>
              <th scope="col"><span class="lbl-long">Date / time</span><span class="lbl-short">Date</span></th>
              <th scope="col">Items</th>
              <th scope="col" class="num">Total</th>
              <th scope="col">Payment</th>
              <th scope="col">Status</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($orders as $o)
              <tr class="{{ $o->trashed() ? 'is-deleted' : '' }}">
                <td data-label="Order"><strong>#{{ $o->id }}</strong></td>
                <td data-label="Date">
                  <span class="d-date">{{ $o->created_at->format('M d, Y') }}</span>
                  <span class="d-time">{{ $o->created_at->format('g:i A') }}</span>
                </td>
                <td data-label="Items"><span class="items-text">{{ $o->items->map(fn ($i) => $i->qty . '× ' . $i->name)->implode(', ') }}</span></td>
                <td data-label="Total" class="num key">₱{{ number_format($o->total, 2) }}</td>
                <td data-label="Payment">{{ $o->payment_method }}</td>
                <td data-label="Status">
                  <div class="hist-status">
                    @if ($o->trashed())
                      <span class="badge bad">Deleted</span>
                    @else
                      <span class="badge {{ $o->status === 'completed' ? 'available' : 'hidden' }}">{{ ucfirst($o->status) }}</span>
                      @if ($o->edited_at)
                        <span class="badge edited">Edited</span>
                      @endif
                    @endif
                  </div>

                  @if ($o->edited_at)
                    <span class="audit-note">
                      <strong>{{ $o->edited_by_name ?: 'Staff' }}</strong> edited {{ $o->edited_at->format('M d, g:i A') }}
                    </span>
                    <span class="audit-reason">Reason: {{ $o->edit_reason ?: '—' }}</span>
                  @endif

                  @if ($o->trashed())
                    <span class="audit-note">
                      <strong>{{ $o->deleted_by_name ?: 'Staff' }}</strong> deleted {{ $o->deleted_at?->format('M d, g:i A') }}
                    </span>
                    <span class="audit-reason">Reason: {{ $o->delete_reason ?: '—' }}</span>
                  @endif
                </td>
                <td data-label="Actions" class="row-actions">
                  <div class="row-actions-wrap">
                    <a class="btn btn-soft btn-sm" href="{{ route('staff.receipt', $o) }}">View</a>
                    @if (! $o->trashed())
                      <a class="btn btn-soft btn-sm" href="{{ route('staff.receipt', [$o, 'print' => 1]) }}">Print</a>
                      <a class="btn btn-soft btn-sm" href="{{ route('staff.orders.edit', $o) }}">Edit</a>
                      <button type="button" class="btn btn-danger btn-sm"
                        data-delete-order="{{ $o->id }}"
                        data-delete-action="{{ route('staff.orders.destroy', $o) }}">Delete</button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr class="hist-empty">
                <td colspan="7">
                  <span class="hist-empty-box">No orders yet.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Phones: cards with a proper actions grid --}}
      @include('staff._order_cards', ['orders' => $orders, 'full' => true])

      @if ($orders->hasPages())
        <div class="pager">{{ $orders->links('pagination::simple-default') }}</div>
      @endif
    </div>
  </div>

  @include('staff._delete_order_modal')
@endsection
