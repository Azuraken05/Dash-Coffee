@extends('layouts.staff')
@section('title', 'Inventory')
@section('content')
    @php
        $f = fn($n) => \App\Models\Ingredient::fmt($n);
        $keep = request()->except('page');
        $tab = fn($k) => route('staff.inventory', array_merge($keep, ['filter' => $k]));
        $hidden = fn(array $keys) => collect(request()->only($keys))->filter();
    @endphp

    <div class="rp-head">
        <div>
            <h1 class="pos-title">Inventory Management</h1>
        </div>
        <div class="rp-clock"><small id="clkDate"></small><strong id="clkTime"></strong></div>
    </div>

    <div class="tabs">
        <a class="tab active" href="{{ route('staff.inventory') }}">Stock</a>
        @if (auth()->user()->role === 'owner')
            <a class="tab" href="{{ route('staff.inv-reports') }}">Reports</a>
        @endif
    </div>

    @if (session('ok'))
        <div class="alert-ok">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert-low">
            <p>{{ $errors->first() }}</p>
        </div>
    @endif
    @include('staff._low', ['low' => $alerts])

    <div class="inv-cards">
        <a class="inv-card {{ $filter === 'all' ? 'on' : '' }}" href="{{ $tab('all') }}"><small>TOTAL
                INGREDIENTS</small><strong>{{ $totals['all'] }}</strong><em>Registered items</em></a>
        <a class="inv-card warn {{ $filter === 'low' ? 'on' : '' }}" href="{{ $tab('low') }}"><small>LOW
                STOCK</small><strong>{{ $totals['low'] }}</strong><em>At or below threshold</em></a>
        <a class="inv-card bad {{ $filter === 'out' ? 'on' : '' }}" href="{{ $tab('out') }}"><small>OUT OF
                STOCK</small><strong>{{ $totals['out'] }}</strong><em>Needs replenishment</em></a>
        <a class="inv-card" href="#history"><small>STOCK MOVEMENTS</small><strong>{{ $totals['moves'] }}</strong><em>Last 7
                days</em></a>
    </div>

    <h2 class="rp-sec">Ingredients</h2>
    <form class="inv-bar" method="GET" action="{{ route('staff.inventory') }}">
        @foreach ($hidden(['hq', 'hfrom', 'hto', 'ingredient']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
        @endforeach
        <input type="hidden" name="filter" value="{{ $filter }}" />
        <input class="search" type="search" name="q" value="{{ request('q') }}"
            placeholder="Search ingredients..." />
        <select name="sort" onchange="this.form.submit()">
            <option value="name" @selected($sort === 'name')>Sort: Name</option>
            <option value="qty_asc" @selected($sort === 'qty_asc')>Sort: Quantity (low → high)</option>
            <option value="qty_desc" @selected($sort === 'qty_desc')>Sort: Quantity (high → low)</option>
        </select>
        <button class="btn btn-primary">Search</button>
    </form>
    <div class="tabs">
        @foreach (['all' => 'All Ingredients', 'low' => 'Low Stock', 'out' => 'Out of Stock'] as $k => $label)
            <a class="tab {{ $filter === $k ? 'active' : '' }}" href="{{ $tab($k) }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- Row 1: ingredient table + Add ingredient --}}
    <div class="inv-row">
        <div class="rp-card">
            <div class="rp-scroll">
                <table class="rp-table">
                    <tr>
                        <th>Ingredient</th>
                        <th class="num">Stock</th>
                        <th>Unit</th>
                        <th class="num">Threshold</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    @foreach ($ingredients as $i)
                        @php $st = (float) $i->stock <= 0 ? 'out' : ($i->isLow() ? 'low' : 'ok'); @endphp
                        <tr>
                            <td><strong>{{ $i->name }}</strong></td>
                            <td class="num key">{{ $f($i->stock) }}</td>
                            <td>{{ $i->unit }}</td>
                            <td class="num">{{ $f($i->low_level) }}</td>
                            <td><span
                                    class="badge {{ ['ok' => 'available', 'low' => 'low', 'out' => 'out_of_stock'][$st] }}">{{ ['ok' => 'In stock', 'low' => 'Low', 'out' => 'Out of stock'][$st] }}</span>
                            </td>
                            <td class="row-actions">
                                <button type="button" class="btn btn-soft btn-sm" data-pick data-id="{{ $i->id }}"
                                    data-name="{{ $i->name }}" data-stock="{{ $i->stock }}"
                                    data-unit="{{ $i->unit }}">Select</button>
                                <button type="button" class="btn btn-soft btn-sm" data-edit data-id="{{ $i->id }}"
                                    data-name="{{ $i->name }}" data-unit="{{ $i->unit }}"
                                    data-low="{{ $i->low_level }}">Edit</button>
                                <a class="btn btn-soft btn-sm"
                                    href="{{ route('staff.inventory', array_merge($keep, ['ingredient' => $i->id])) }}#history">History</a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            @if ($ingredients->isEmpty())
                <div class="empty">No ingredients match.</div>
            @endif
        </div>
        <aside class="card" id="ingCard">
            <h3 id="ingTitle">Add ingredient</h3>
            <form method="POST" action="{{ route('staff.inventory.store') }}" id="ingForm"
                data-base="{{ url('staff/inventory') }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="ingMethod" disabled />
                <label>Ingredient name</label><input type="text" name="name" id="ingName" required />
                <label>Unit</label>
                <select name="unit" id="ingUnit" required>
                    <option value="">Select unit</option>
                    <option>pcs</option>
                    <option>g</option>
                    <option>kg</option>
                    <option>ml</option>
                    <option>L</option>
                </select>
                <div id="ingStockBox"><label>Initial stock</label><input type="number" name="stock" id="ingStock"
                        step="0.01" min="0" value="0" required /></div>
                <label>Low-stock threshold</label><input type="number" name="low_level" id="ingLow" step="0.01"
                    min="0" value="0" required />
                <div class="actions end"><button type="button" class="btn btn-soft" id="ingCancel"
                        hidden>Cancel</button><button class="btn btn-primary" id="ingSave">Save</button></div>
            </form>
        </aside>
    </div>

    {{-- Row 2: stock history + Quick stock adjustment --}}
    <h2 class="rp-sec">Stock movements</h2>
    <div class="inv-row">
        <div class="rp-card" id="history">
            <h3>Stock history @if ($filterIngredient)
                    <small>for {{ $filterIngredient->name }} · <a
                            href="{{ route('staff.inventory', request()->except(['ingredient', 'page'])) }}#history">show
                            all</a></small>
                @endif
            </h3>
            <form class="inv-bar" method="GET" action="{{ route('staff.inventory') }}">
                @foreach ($hidden(['q', 'filter', 'sort', 'ingredient']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                @endforeach
                <input class="search" type="search" name="hq" value="{{ request('hq') }}"
                    placeholder="Search ingredient, reason, staff" />
                <label>From <input type="date" name="hfrom" value="{{ request('hfrom') }}" /></label>
                <label>To <input type="date" name="hto" value="{{ request('hto') }}" /></label>
                <button class="btn btn-primary">Filter</button>
            </form>
            <div class="rp-scroll">
                <table class="rp-table">
                    <tr>
                        <th>Date</th>
                        <th>Ingredient</th>
                        <th>Action</th>
                        <th class="num">Change</th>
                        <th class="num">Before → After</th>
                        <th>Staff</th>
                        <th>Reason</th>
                    </tr>
                    @foreach ($movements as $m)
                        <tr>
                            <td>{{ $m->created_at->format('M d, g:i A') }}</td>
                            <td>{{ $m->ingredient->name }}</td>
                            <td>{{ ['in' => 'Stock In', 'out' => 'Stock Out', 'used' => 'Used'][$m->type] ?? $m->type }}
                            </td>
                            <td class="num key {{ $m->quantity < 0 ? 'neg' : '' }}">
                                {{ $m->quantity > 0 ? '+' : '' }}{{ $f($m->quantity) }} {{ $m->ingredient->unit }}</td>
                            <td class="num">
                                {{ $m->previous_stock !== null ? $f($m->previous_stock) . ' → ' . $f($m->resulting_stock) : '—' }}
                            </td>
                            <td>{{ $m->user->name ?? '—' }}</td>
                            <td>{{ $m->reason }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            @if ($movements->isEmpty())
                <div class="empty">No stock movements found.</div>
            @endif
            <div class="pager">{{ $movements->links('pagination::simple-default') }}</div>
        </div>

        <aside class="card" id="adjCard">
            <h3>Quick stock adjustment</h3>
            <form method="POST" action="{{ route('staff.inventory.adjust') }}">
                @csrf
                <input type="hidden" name="ingredient_id" id="pickId" />
                <div class="note" id="pickInfo">No ingredient selected<small>Click Select in the ingredient
                        table</small></div>
                <label>Quantity</label><input type="number" name="quantity" id="adjQty" step="0.01"
                    min="0.01" placeholder="0.0" required />
                <label>Reason (required)</label><input type="text" name="reason"
                    placeholder="e.g. New delivery, spillage, recount" required />
                <p class="preview" id="preview">Select an ingredient first.</p>
                <div class="actions">
                    <button class="btn btn-primary" name="action" value="in" id="btnAdd" disabled>+ Add
                        Stock</button>
                    <button class="btn btn-danger" name="action" value="out" id="btnDeduct" disabled>− Deduct
                        Stock</button>
                </div>
            </form>
        </aside>
    </div>

    <script>
        var sel = null,
            n = function(x) {
                return Math.round(x * 100) / 100;
            };
        var qty = document.getElementById('adjQty'),
            addB = document.getElementById('btnAdd'),
            dedB = document.getElementById('btnDeduct'),
            prev = document.getElementById('preview');

        function calc() {
            if (!sel) {
                prev.textContent = 'Select an ingredient first.';
                addB.disabled = dedB.disabled = true;
                return;
            }
            var q = parseFloat(qty.value) || 0,
                up = sel.stock + q,
                down = sel.stock - q;
            prev.innerHTML = q > 0 ? 'Now <strong>' + n(sel.stock) + '</strong> → add: <strong>' + n(up) +
                '</strong> · deduct: <strong class="' + (down < 0 ? 'neg' : '') + '">' + n(down) + '</strong> ' + sel.unit :
                'Now <strong>' + n(sel.stock) + '</strong> ' + sel.unit;
            addB.disabled = !(q > 0);
            dedB.disabled = !(q > 0) || down < 0;
        }
        qty.addEventListener('input', calc);
        document.querySelectorAll('[data-pick]').forEach(function(b) {
            b.addEventListener('click', function() {
                sel = {
                    id: b.dataset.id,
                    stock: parseFloat(b.dataset.stock),
                    unit: b.dataset.unit
                };
                document.getElementById('pickId').value = sel.id;
                document.getElementById('pickInfo').textContent = b.dataset.name + ' · current ' + n(sel
                    .stock) + ' ' + sel.unit;
                calc();
                document.getElementById('adjCard').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                qty.focus({
                    preventScroll: true
                });
            });
        });

        var form = document.getElementById('ingForm'),
            addAction = form.action;
        document.querySelectorAll('[data-edit]').forEach(function(b) {
            b.addEventListener('click', function() {
                form.action = form.dataset.base + '/' + b.dataset.id;
                document.getElementById('ingMethod').disabled = false;
                document.getElementById('ingName').value = b.dataset.name;
                document.getElementById('ingUnit').value = b.dataset.unit;
                document.getElementById('ingLow').value = b.dataset.low;
                document.getElementById('ingStock').disabled = true;
                document.getElementById('ingStockBox').hidden = true;
                document.getElementById('ingTitle').textContent = 'Edit ingredient';
                document.getElementById('ingSave').textContent = 'Update';
                document.getElementById('ingCancel').hidden = false;
            });
        });
        document.getElementById('ingCancel').addEventListener('click', function() {
            form.reset();
            form.action = addAction;
            document.getElementById('ingMethod').disabled = true;
            document.getElementById('ingStock').disabled = false;
            document.getElementById('ingStockBox').hidden = false;
            document.getElementById('ingTitle').textContent = 'Add ingredient';
            document.getElementById('ingSave').textContent = 'Save';
            this.hidden = true;
        });
    </script>
@endsection
