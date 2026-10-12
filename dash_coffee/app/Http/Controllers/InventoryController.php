<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class InventoryController extends Controller
{
    public function index(Request $r)
    {
        $filter = in_array($r->query('filter'), ['low', 'out']) ? $r->query('filter') : 'all';
        $sort = in_array($r->query('sort'), ['qty_asc', 'qty_desc']) ? $r->query('sort') : 'name';

        $ingredients = Ingredient::query()
            ->when($r->query('q'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($filter === 'out', fn($q) => $q->where('stock', '<=', 0))
            ->when($filter === 'low', fn($q) => $q->where('stock', '>', 0)->whereColumn('stock', '<=', 'low_level'))
            ->when($sort === 'name', fn($q) => $q->orderBy('name'))
            ->when($sort === 'qty_asc', fn($q) => $q->orderBy('stock'))
            ->when($sort === 'qty_desc', fn($q) => $q->orderByDesc('stock'))
            ->get();

        $movements = StockMovement::with('ingredient', 'user')
            ->when($r->query('ingredient'), fn($q, $id) => $q->where('ingredient_id', $id))
            ->when($r->query('hq'), function ($q, $s) {
                $q->where(fn($w) => $w->where('reason', 'like', "%{$s}%")
                    ->orWhereHas('ingredient', fn($i) => $i->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$s}%")));
            })
            ->when($r->query('hfrom'), fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($r->query('hto'), fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()->paginate(10)->appends($r->query());

        return view('staff.inventory', [
            'ingredients' => $ingredients,
            'movements' => $movements,
            'filter' => $filter,
            'sort' => $sort,
            'totals' => [
                'all' => Ingredient::count(),
                'low' => Ingredient::where('stock', '>', 0)->whereColumn('stock', '<=', 'low_level')->count(),
                'out' => Ingredient::where('stock', '<=', 0)->count(),
                'moves' => StockMovement::where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'alerts' => Ingredient::whereColumn('stock', '<=', 'low_level')->orderBy('stock')->get(),
            'filterIngredient' => $r->query('ingredient') ? Ingredient::find($r->query('ingredient')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:100|unique:ingredients,name',
            'unit' => 'required|in:pcs,g,kg,ml,L',
            'stock' => 'required|numeric|min:0',
            'low_level' => 'required|numeric|min:0',
        ], ['name.unique' => 'That ingredient already exists. Use a different name.']);

        DB::transaction(function () use ($d) {
            $ing = Ingredient::create($d);
            if ($d['stock'] > 0) {
                $this->log($ing, 'in', $d['stock'], 0, $d['stock'], 'Initial stock');
            }
        });

        return back()->with('ok', $d['name'] . ' added.');
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('ingredients', 'name')->ignore($ingredient->id)],
            'unit' => 'required|in:pcs,g,kg,ml,L',
            'low_level' => 'required|numeric|min:0',
        ], ['name.unique' => 'That ingredient already exists. Use a different name.']);

        $ingredient->update($d);

        return back()->with('ok', $ingredient->name . ' updated.');
    }

    public function adjust(Request $request)
    {
        $d = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'action' => 'required|in:in,out',
            'quantity' => 'required|numeric|gt:0',
            'reason' => 'required|string|max:150',
        ], ['reason.required' => 'Please enter a reason for this adjustment.']);

        DB::transaction(function () use ($d) {
            $ing = Ingredient::lockForUpdate()->findOrFail($d['ingredient_id']);
            $prev = (float) $ing->stock;
            $qty = (float) $d['quantity'];
            $new = $d['action'] === 'in' ? $prev + $qty : $prev - $qty;

            if ($new < 0) {
                throw ValidationException::withMessages(['quantity' => 'That would make stock negative. Only ' . Ingredient::fmt($prev) . ' ' . $ing->unit . ' available.']);
            }

            $ing->stock = $new;
            $ing->save();
            $this->log($ing, $d['action'], $d['action'] === 'in' ? $qty : -$qty, $prev, $new, $d['reason']);
        });

        return back()->with('ok', 'Stock updated.');
    }

    private function log(Ingredient $ing, string $type, int|float $qty, int|float $prev, int|float $new, string $reason): void
    {
        StockMovement::create([
            'ingredient_id' => $ing->id,
            'type' => $type,
            'quantity' => $qty,
            'previous_stock' => $prev,
            'resulting_stock' => $new,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);
    }
}
