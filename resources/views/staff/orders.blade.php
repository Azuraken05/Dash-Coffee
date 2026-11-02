@extends('layouts.staff')
@section('title', 'Order Entry')
@section('content')
    @if ($errors->any())
        <div class="alert-low">
            <p>{{ $errors->first() }}</p>
        </div>
    @endif
    <div class="split-layout">
        <section>
            <div class="rp-head">
                <div>
                    <h1 class="pos-title">Order Entry</h1>
                </div>
                <div class="rp-clock"><small id="clkDate"></small><strong id="clkTime"></strong></div>
            </div>
            <div class="toolbar">
                <div class="tabs" id="catTabs">
                    <button type="button" class="tab active" data-cat="">All Products</button>
                    @foreach ($products->pluck('category')->unique()->sort() as $c)
                        <button type="button" class="tab" data-cat="{{ $c }}">{{ $c }}</button>
                    @endforeach
                </div>
                <input class="search" id="prodSearch" type="search" placeholder="Search products" />
            </div>
            <div class="prod-grid" id="prodGrid">
                @foreach ($products as $p)
                    @php $ok = $p->displayStatus() === 'available'; @endphp
                    <button type="button" class="prod-tile {{ $ok ? '' : 'off' }}" @disabled(!$ok)
                        data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-price="{{ $p->price }}"
                        data-cat="{{ $p->category }}">
                        <!-- Updated: Image now loads from S3 Cloud Storage -->
                        <img class="tile-img" src="{{ $p->image ? \Illuminate\Support\Facades\Storage::disk('s3')->url($p->image) : asset('assets/dashcoffee-logo.jpg') }}"
                            alt="{{ $p->name }}" />
                        <strong>{{ $p->name }}</strong>
                        <span>₱{{ number_format($p->price, 2) }}</span>
                        @unless ($ok)
                            <em class="badge out_of_stock">Out of Stock</em>
                        @endunless
                    </button>
                @endforeach
            </div>
            @if ($products->isEmpty())
                <div class="empty">No products yet. Add them in Manage Menu.</div>
            @endif
        </section>

        <aside class="card order-panel">
            <h3>Current order</h3>
            <form method="POST" action="{{ route('staff.orders.store') }}" id="orderForm">
                @csrf
                <div id="cartLines">
                    <div class="empty">Tap a product to add it.</div>
                </div>
                <div id="cartInputs"></div>
                <div class="order-foot">
                    <div class="total"><span>Total</span><strong id="cartTotal">₱0.00</strong></div>
                    <label>Payment method</label>
                    <select name="method" id="payMethod">
                        <option>Cash</option>
                        <option>GCash</option>
                        <option>Bank Transfer</option>
                        <option>Maya</option>
                    </select>
                    <div id="cashBox"><label>Cash received</label><input type="number" name="cash" id="cashIn"
                            step="0.01" min="0" />
                        <p class="change" id="changeOut"></p>
                    </div>
                    <div id="refBox" hidden><label>Reference number</label><input type="text" name="reference" />
                    </div>
                    <div class="actions"><button type="button" class="btn btn-danger" id="clearCart">Clear
                            Order</button><button class="btn btn-primary">Confirm Order</button></div>
                </div>
                <!-- QR Payment Modal -->
                <div class="qr-payment-modal" id="qrPaymentModal" hidden>
                    <div class="qr-payment-card">
                        <button type="button" class="qr-close" id="qrClose">&times;</button>

                        <h2 id="qrPaymentTitle">Scan to Pay</h2>
                        <p class="qr-payment-subtitle">
                            Scan the QR code using your payment app.
                        </p>

                        <div class="qr-placeholder" id="qrPlaceholder">
                            <span>QR CODE</span>
                            <small>Insert your QR image here later</small>
                        </div>

                        <p class="qr-payment-total">
                            Total to pay:
                            <strong id="qrPaymentTotal">₱0.00</strong>
                        </p>

                        <button type="button" class="btn btn-primary" id="paymentReceived">
                            Payment Received
                        </button>
                    </div>
                </div>
            </form>
        </aside>
    </div>
    <script>
        var cart = {},
            peso = function(n) {
                return '₱' + n.toFixed(2);
            };
        var lines = document.getElementById('cartLines'),
            inputs = document.getElementById('cartInputs');

        function total() {
            var t = 0;
            for (var id in cart) t += cart[id].price * cart[id].qty;
            return t;
        }

        function render() {
            lines.innerHTML = '';
            inputs.innerHTML = '';
            var ids = Object.keys(cart);
            if (!ids.length) lines.innerHTML = '<div class="empty">Tap a product to add it.</div>';
            ids.forEach(function(id) {
                var c = cart[id],
                    row = document.createElement('div');
                row.className = 'cart-line';
                var name = document.createElement('span');
                name.textContent = c.name + ' × ' + c.qty;
                var amt = document.createElement('strong');
                amt.textContent = peso(c.price * c.qty);
                var minus = document.createElement('button');
                minus.type = 'button';
                minus.textContent = '−';
                minus.onclick = function() {
                    if (--c.qty < 1) delete cart[id];
                    render();
                };
                row.append(name, amt, minus);
                lines.appendChild(row);
                var h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'items[' + id + ']';
                h.value = c.qty;
                inputs.appendChild(h);
            });
            document.getElementById('cartTotal').textContent = peso(total());
            cash();
        }

        function cash() {
            var v = parseFloat(document.getElementById('cashIn').value || 0),
                t = total();
            document.getElementById('changeOut').textContent = v >= t && t > 0 ? 'Change: ' + peso(v - t) : '';
        }
        document.getElementById('prodGrid').addEventListener('click', function(e) {
            var b = e.target.closest('.prod-tile');
            if (!b || b.disabled) return;
            var id = b.dataset.id;
            cart[id] = cart[id] || {
                name: b.dataset.name,
                price: parseFloat(b.dataset.price),
                qty: 0
            };
            cart[id].qty++;
            render();
        });
        document.getElementById('clearCart').onclick = function() {
            cart = {};
            render();
        };
        document.getElementById('cashIn').addEventListener('input', cash);
        document.getElementById('payMethod').addEventListener('change', function() {
            var c = this.value === 'Cash';
            document.getElementById('cashBox').hidden = !c;
            document.getElementById('refBox').hidden = c;
        });
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            if (!Object.keys(cart).length) e.preventDefault();
        });
        var cat = '',
            q = '';

        function filter() {
            document.querySelectorAll('.prod-tile').forEach(function(t) {
                t.hidden = (cat && t.dataset.cat !== cat) || (q && t.dataset.name.toLowerCase().indexOf(q) < 0);
            });
        }
        document.getElementById('catTabs').addEventListener('click', function(e) {
            var t = e.target.closest('.tab');
            if (!t) return;
            this.querySelectorAll('.tab').forEach(function(x) {
                x.classList.remove('active');
            });
            t.classList.add('active');
            cat = t.dataset.cat;
            filter();
        });
        document.getElementById('prodSearch').addEventListener('input', function() {
            q = this.value.toLowerCase();
            filter();
        });
    </script>
    @if (!empty($receipt))
        <div class="receipt-modal" id="receiptModal">
            <div class="receipt-box">
                @include('staff._receipt', ['order' => $receipt])
                <div class="receipt-actions no-print">
                    <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
                    <button type="button" class="btn btn-soft" id="receiptClose">New Order</button>
                </div>
            </div>
        </div>
        <script>
            var rm = document.getElementById('receiptModal');

            function closeRm() {
                if (rm) rm.remove();
            }
            document.getElementById('receiptClose').onclick = closeRm;
            rm.addEventListener('click', function(e) {
                if (e.target === rm) closeRm();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeRm();
            });
        </script>
    @endif
@endsection