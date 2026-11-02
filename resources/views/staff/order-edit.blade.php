@extends('layouts.staff')
@section('title', 'Edit Order #' . $order->id)
@section('content')
  <div class="order-edit">
    <div class="rp-head">
      <div>
        <h1 class="pos-title">Edit Order #{{ $order->id }}</h1>
        <p class="staff-sub">
          {{ $order->created_at->format('M d, Y g:i A') }} ·
          Cashier: {{ $order->user->name ?? '—' }}
        </p>
      </div>
      <div class="rp-clock"><small id="clkDate"></small><strong id="clkTime"></strong></div>
    </div>

    @if ($order->edited_at)
      <div class="alert-ok">
        This order was last edited by <strong>{{ $order->edited_by_name ?: 'Staff' }}</strong>
        on {{ $order->edited_at->format('M d, Y g:i A') }}.
        @if ($order->edit_reason)
          Reason: {{ $order->edit_reason }}
        @endif
      </div>
    @endif

    @if ($errors->any())
      <div class="alert-low"><p>{{ $errors->first() }}</p></div>
    @endif

    <form method="POST" action="{{ route('staff.orders.update', $order) }}" class="rp-card order-edit-card">
      @csrf
      @method('PUT')

      <h3>Order items <small>Set the quantity to 0 to remove an item</small></h3>

      <div class="rp-scroll">
        <table class="rp-table edit-table">
          <thead>
            <tr>
              <th>Item</th>
              <th class="num">Price</th>
              <th class="num">Qty</th>
              <th class="num">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($order->items as $item)
              <tr data-price="{{ $item->price }}">
                <td>
                  {{ $item->name }}
                  @if (! $item->product_id)
                    <small class="muted">(product removed from menu)</small>
                  @endif
                </td>
                <td class="num">₱{{ number_format($item->price, 2) }}</td>
                <td class="num">
                  <input class="edit-qty" type="number" name="items[{{ $item->id }}]"
                    value="{{ old('items.' . $item->id, $item->qty) }}"
                    min="0" max="999" step="1" inputmode="numeric" />
                </td>
                <td class="num edit-subtotal">₱{{ number_format($item->price * $item->qty, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="edit-grid">
        <label>Add item
          <select name="add_product" id="addProduct">
            <option value="">— none —</option>
            @foreach ($products as $p)
              <option value="{{ $p->id }}" data-price="{{ $p->price }}"
                @selected((string) old('add_product') === (string) $p->id)>
                {{ $p->name }} — ₱{{ number_format($p->price, 2) }}
              </option>
            @endforeach
          </select>
        </label>

        <label>Quantity
          <input type="number" name="add_qty" id="addQty" value="{{ old('add_qty', 1) }}"
            min="1" max="999" step="1" inputmode="numeric" />
        </label>

        <label>Payment method
          <select name="method" id="editMethod">
            @foreach (['Cash', 'GCash', 'Bank Transfer', 'Maya'] as $m)
              <option value="{{ $m }}" @selected(old('method', $order->payment_method) === $m)>{{ $m }}</option>
            @endforeach
          </select>
        </label>

        <label id="editRefField">Reference
          <input type="text" name="reference" value="{{ old('reference', $order->reference) }}" maxlength="50" />
        </label>

        <label id="editCashField">Cash received
          <input type="number" name="cash" id="editCash" value="{{ old('cash', $order->cash_received) }}"
            min="0" step="0.01" inputmode="decimal" />
          <small class="muted">Must be at least the new total.</small>
        </label>
      </div>

      <div class="edit-total">
        <span>New total</span>
        <strong id="editTotal">₱{{ number_format($order->total, 2) }}</strong>
      </div>

      <label class="edit-reason" for="editReason">
        Reason for editing <span class="req">*</span>
        <textarea id="editReason" name="reason" rows="3" maxlength="255" required
          placeholder="e.g., Customer changed their order / wrong item entered">{{ old('reason') }}</textarea>
      </label>

      <div class="edit-actions">
        <button class="btn btn-primary">Save changes</button>
        <a class="btn btn-soft" href="{{ route('staff.history') }}">Cancel</a>
      </div>
    </form>
  </div>

  <script>
    (function () {
      var form = document.querySelector('.order-edit-card');
      if (!form) return;

      var currency = function (value) {
        return '₱' + Number(value).toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
      };

      var rows = Array.prototype.slice.call(form.querySelectorAll('.edit-table tbody tr'));
      var addProduct = document.getElementById('addProduct');
      var addQty = document.getElementById('addQty');
      var totalOut = document.getElementById('editTotal');
      var method = document.getElementById('editMethod');
      var refField = document.getElementById('editRefField');
      var cashField = document.getElementById('editCashField');

      function recalc() {
        var total = 0;

        rows.forEach(function (row) {
          var price = parseFloat(row.getAttribute('data-price')) || 0;
          var input = row.querySelector('.edit-qty');
          var qty = Math.max(0, parseInt(input.value, 10) || 0);
          var subtotal = price * qty;
          total += subtotal;
          row.querySelector('.edit-subtotal').textContent = currency(subtotal);
        });

        if (addProduct && addProduct.value) {
          var option = addProduct.options[addProduct.selectedIndex];
          var addPrice = parseFloat(option.getAttribute('data-price')) || 0;
          var qty = Math.max(0, parseInt(addQty.value, 10) || 0);
          total += addPrice * qty;
        }

        totalOut.textContent = currency(total);
      }

      function syncPaymentFields() {
        var isCash = method.value === 'Cash';
        cashField.hidden = !isCash;
        refField.hidden = isCash;
      }

      form.querySelectorAll('.edit-qty').forEach(function (input) {
        input.addEventListener('input', recalc);
      });
      if (addProduct) addProduct.addEventListener('change', recalc);
      if (addQty) addQty.addEventListener('input', recalc);
      if (method) method.addEventListener('change', syncPaymentFields);

      recalc();
      syncPaymentFields();
    })();
  </script>
@endsection
