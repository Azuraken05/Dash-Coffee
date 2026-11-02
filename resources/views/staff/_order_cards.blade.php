{{--
  Mobile order cards (shown at 900px and below; the table is hidden there).
  Used by: staff/dashboard.blade.php (View only) and staff/history.blade.php (all actions).

  Params:
    $orders     collection or paginator of Order
    $full       true  = View + Print + Edit + Delete
                false = View only (default)
    $clickable  true  = tapping the card opens the receipt (default false)
--}}
@php
    $full = $full ?? false;
    $clickable = $clickable ?? false;
@endphp

<div class="ord-cards">
    @forelse ($orders as $o)
        @php
            $trashed = $o->trashed();
            $itemsText = $o->items->map(fn ($i) => $i->qty . '× ' . $i->name)->implode(', ');
        @endphp

        <article class="ord-card {{ $trashed ? 'is-deleted' : '' }}" aria-label="Order #{{ $o->id }}"
            @if ($clickable) data-href="{{ route('staff.receipt', $o) }}" @endif>

            {{-- Order # + status --}}
            <div class="ord-pair">
                <div>
                    <span class="ord-lbl">Order</span>
                    <div class="ord-val ord-id">#{{ $o->id }}</div>
                </div>
                <div>
                    <span class="ord-lbl">Status</span>
                    <div class="ord-status">
                        @if ($trashed)
                            <span class="badge bad">Deleted</span>
                        @else
                            <span class="badge {{ $o->status === 'completed' ? 'available' : 'hidden' }}">{{ ucfirst($o->status) }}</span>
                            @if ($o->edited_at)
                                <span class="badge edited">Edited</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Date --}}
            <div>
                <span class="ord-lbl">Date</span>
                <div class="ord-val">{{ $o->created_at->format('M d, Y') }} · {{ $o->created_at->format('g:i A') }}</div>
            </div>

            {{-- Items --}}
            <div>
                <span class="ord-lbl">Items</span>
                <div class="ord-val">{{ $itemsText }}</div>
            </div>

            {{-- Total + payment method --}}
            <div class="ord-pair">
                <div>
                    <span class="ord-lbl">Total</span>
                    <div class="ord-val ord-money">₱{{ number_format($o->total, 2) }}</div>
                </div>
                <div>
                    <span class="ord-lbl">Via</span>
                    <div class="ord-val">{{ $o->payment_method }}</div>
                </div>
            </div>

            {{-- Audit trail: edited / deleted --}}
            @if ($o->edited_at)
                <div class="ord-note">
                    <strong>{{ $o->edited_by_name ?: 'Staff' }}</strong> edited {{ $o->edited_at->format('M d, g:i A') }}
                    <em>Reason: {{ $o->edit_reason ?: '—' }}</em>
                </div>
            @endif
            @if ($trashed)
                <div class="ord-note del">
                    <strong>{{ $o->deleted_by_name ?: 'Staff' }}</strong> deleted {{ $o->deleted_at?->format('M d, g:i A') }}
                    <em>Reason: {{ $o->delete_reason ?: '—' }}</em>
                </div>
            @endif

            {{-- Actions --}}
            <div class="ord-foot">
                <span class="ord-lbl">Actions</span>
                <div class="ord-actions">
                    <a class="ord-act" href="{{ route('staff.receipt', $o) }}">View</a>
                    @if ($full && ! $trashed)
                        <a class="ord-act" href="{{ route('staff.receipt', [$o, 'print' => 1]) }}">Print</a>
                        <a class="ord-act" href="{{ route('staff.orders.edit', $o) }}">Edit</a>
                        <button type="button" class="ord-act danger"
                            data-delete-order="{{ $o->id }}"
                            data-delete-action="{{ route('staff.orders.destroy', $o) }}">Delete</button>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="ord-empty">No orders yet.</div>
    @endforelse
</div>
