@extends('layouts.staff')
@section('title', 'Manage Menu')
@section('content')
  @if (session('ok'))<div class="alert-ok">{{ session('ok') }}</div>@endif
  @if ($errors->any())<div class="alert-low"><p>{{ $errors->first() }}</p></div>@endif
  <div class="menu-layout">
    <aside class="card">
      <h3>Products</h3>
      @forelse ($products as $p)
        <div class="prod-row">
          <div class="prod-info">
            <img class="prod-thumb" src="{{ $p->image ? asset($p->image) : asset('images/dashcoffee-logo.jpg') }}" alt="{{ $p->name }}" />
            <div><strong>{{ $p->name }}</strong><small>{{ $p->category }} · ₱{{ number_format($p->price, 2) }}</small></div>
          </div>
          <span class="badge {{ $p->displayStatus() }}">{{ ['available' => 'Available', 'out_of_stock' => 'Out of Stock', 'hidden' => 'Hidden'][$p->displayStatus()] }}</span>
          <form method="POST" action="{{ route('staff.menu.status', $p) }}">
            @csrf @method('PATCH')
            <select name="status" onchange="this.form.submit()">
              @foreach (['available' => 'Available', 'out_of_stock' => 'Out of Stock', 'hidden' => 'Hidden'] as $k => $v)
                <option value="{{ $k }}" @selected($p->status === $k)>{{ $v }}</option>
              @endforeach
            </select>
          </form>
          <form method="POST" action="{{ route('staff.menu.destroy', $p) }}" onsubmit="return confirm('Delete this product?')">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-sm">Delete</button>
          </form>
        </div>
      @empty
        <div class="empty">No products yet.</div>
      @endforelse
    </aside>

    <section class="card">
      <h3>Add product</h3>
      <form method="POST" action="{{ route('staff.menu.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="fields">
                    <label>Product picture <span class="req">*</span></label>
          <div class="img-pick">
            <img id="imgPrev" src="{{ asset('images/dashcoffee-logo.jpg') }}" alt="Preview" />
            <div class="img-meta">
              <input type="file" name="image" id="imgIn" accept="image/png,image/jpeg" required />
              <label for="imgIn" class="btn btn-primary upload-btn"> Upload picture</label>
              <span class="img-name" id="imgName">No picture selected</span>
              <small>JPG or PNG · max 5 MB</small>
            </div>
          </div>
          <label>Product name</label><input type="text" name="name" value="{{ old('name') }}" required />
          <label>Category <small>(type a new one or pick from the list)</small></label>
          <input type="text" name="category" list="catList" value="{{ old('category') }}" placeholder="e.g. Foods, Beverages, Pastries" autocomplete="off" required />
          <datalist id="catList">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
          <label>Price</label><input type="number" name="price" step="0.01" min="0" value="{{ old('price') }}" required />
          <label>Status</label>
          <select name="status"><option value="available">Available</option><option value="out_of_stock">Out of Stock</option><option value="hidden">Hidden</option></select>
        </div>
        <div class="sub-head"><h3>Ingredients (Recipe)</h3><button type="button" class="btn btn-primary btn-sm" id="addRow">+ Add Ingredient</button></div>
        <div id="recipeRows"></div>
        <datalist id="ingList">@foreach ($ingredients as $i)<option value="{{ $i->name }} ({{ $i->unit }})" data-id="{{ $i->id }}" data-name="{{ $i->name }}">@endforeach</datalist>
        <div class="actions end"><button class="btn btn-primary">Save</button></div>
      </form>
    </section>
  </div>

  <template id="rowTpl">
    <div class="recipe-row">
      <input type="text" class="ing-input" list="ingList" placeholder="Type or select ingredient" autocomplete="off" />
      <input type="hidden" name="recipe[__N__][ingredient_id]" class="ing-id" />
      <input type="number" name="recipe[__N__][quantity]" step="0.01" min="0" placeholder="Qty used" />
    </div>
  </template>
  <script>
    var n = 0, box = document.getElementById('recipeRows'), tpl = document.getElementById('rowTpl').innerHTML;
    function addRow() { box.insertAdjacentHTML('beforeend', tpl.replace(/__N__/g, n++)); }
    document.getElementById('addRow').addEventListener('click', addRow);
    addRow();

    // Typeable ingredient: match what was typed to a real ingredient
    var opts = Array.prototype.map.call(document.querySelectorAll('#ingList option'), function (o) {
      return { id: o.dataset.id, label: o.value.toLowerCase(), name: o.dataset.name.toLowerCase() };
    });
    box.addEventListener('input', function (e) {
      if (!e.target.classList.contains('ing-input')) return;
      var v = e.target.value.trim().toLowerCase(), hit = null;
      if (v) {
        hit = opts.filter(function (o) { return o.label === v; })[0];
        if (!hit) { var byName = opts.filter(function (o) { return o.name === v; }); if (byName.length === 1) hit = byName[0]; }
      }
      e.target.nextElementSibling.value = hit ? hit.id : '';
      e.target.setCustomValidity(v && !hit ? 'Pick an ingredient from the list (add it in Inventory first).' : '');
    });

        document.getElementById('imgIn').addEventListener('change', function () {
      var f = this.files[0];
      if (f) {
        document.getElementById('imgPrev').src = URL.createObjectURL(f);
        document.getElementById('imgName').textContent = f.name;
      }
    });
  </script>
@endsection
