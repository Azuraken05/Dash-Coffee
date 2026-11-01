<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function create()
    {
        return view('staff.orders', [
            'products' => Product::with('ingredients')->where('status', '!=', 'hidden')->orderBy('name')->get(),
            'receipt' => session('receipt_id') ? Order::with('items', 'user')->find(session('receipt_id')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'integer|min:1',
            'method' => 'required|in:Cash,GCash,Bank Transfer,Maya',
            'reference' => 'nullable|string|max:50',
            'cash' => 'nullable|numeric|min:0',
        ], ['items.required' => 'The order is empty.']);

        $products = Product::with('ingredients')->whereIn('id', array_keys($d['items']))->get()->keyBy('id');

        $total = 0;
        $need = []; // ingredient_id => total amount needed
        foreach ($d['items'] as $pid => $qty) {
            $p = $products[$pid] ?? null;
            if (! $p || $p->status !== 'available') {
                return back()->withErrors(['items' => 'A product in this order is not available.'])->withInput();
            }
            $total += $p->price * $qty;
            foreach ($p->ingredients as $i) {
                $need[$i->id] = ($need[$i->id] ?? 0) + $i->pivot->quantity * $qty;
            }
        }

        $stock = Ingredient::whereIn('id', array_keys($need))->get()->keyBy('id');
        foreach ($need as $id => $n) {
            if ((float) $stock[$id]->stock < $n) {
                return back()->withErrors(['items' => 'Not enough ' . $stock[$id]->name . ' in stock.'])->withInput();
            }
        }

        $cash = null;
        $change = null;
        if ($d['method'] === 'Cash') {
            $cash = (float) ($d['cash'] ?? 0);
            if ($cash < $total) {
                return back()->withErrors(['cash' => 'Cash received is less than the total.'])->withInput();
            }
            $change = $cash - $total;
        }

        $order = DB::transaction(function () use ($d, $products, $need, $stock, $total, $cash, $change) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'total' => $total,
                'payment_method' => $d['method'],
                'reference' => $d['method'] === 'Cash' ? null : ($d['reference'] ?? null),
                'cash_received' => $cash,
                'change_amount' => $change,
                'status' => 'completed',
            ]);

            foreach ($d['items'] as $pid => $qty) {
                $order->items()->create([
                    'product_id' => $pid,
                    'name' => $products[$pid]->name,
                    'price' => $products[$pid]->price,
                    'qty' => $qty,
                ]);
            }

            // Automatic inventory deduction
            foreach ($need as $id => $n) {
                $ing = $stock[$id];
                $before = (float) $ing->stock;
                $ing->stock = $before - $n;
                $ing->save();
                StockMovement::create([
                    'ingredient_id' => $id,
                    'type' => 'used',
                    'quantity' => -$n,
                    'previous_stock' => $before,
                    'resulting_stock' => $ing->stock,
                    'reason' => 'Order #' . $order->id,
                    'user_id' => auth()->id(),
                ]);
            }

            return $order;
        });

        return redirect()->route('staff.orders')->with('receipt_id', $order->id);
    }

    public function history()
    {
        return view('staff.history', [
            // Include soft-deleted orders so staff can see the deletion audit trail.
            'orders' => Order::withTrashed()->with('items', 'user')->latest()->paginate(15),
        ]);
    }

    public function edit(Order $order)
    {
        $order->load('items');

        return view('staff.order-edit', [
            'order' => $order,
            'products' => Product::with('ingredients')
                ->where('status', 'available')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $d = $request->validate([
            'items' => 'required|array',
            'items.*' => 'integer|min:0|max:999',
            'add_product' => 'nullable|integer|exists:products,id',
            'add_qty' => 'nullable|integer|min:1|max:999',
            'method' => 'required|in:Cash,GCash,Bank Transfer,Maya',
            'reference' => 'nullable|string|max:50',
            'cash' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:255',
        ], [
            'items.required' => 'An order must have at least one item.',
            'reason.required' => 'Please enter the reason for editing this order.',
        ]);

        DB::transaction(function () use ($order, $d) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $order->load('items');

            // Build the new order lines. A quantity of 0 removes that item.
            $lines = [];
            foreach ($order->items as $item) {
                $qty = (int) ($d['items'][$item->id] ?? 0);
                if ($qty < 1) {
                    continue;
                }

                $lines[] = [
                    'item' => $item,
                    'product_id' => $item->product_id ? (int) $item->product_id : null,
                    'name' => $item->name,
                    'price' => (float) $item->price,
                    'qty' => $qty,
                ];
            }

            // Optional new item. If it is already in the order, just add to its quantity.
            if (! empty($d['add_product'])) {
                $product = Product::with('ingredients')->find($d['add_product']);
                if (! $product || $product->status !== 'available') {
                    throw ValidationException::withMessages([
                        'add_product' => 'That product is not available right now.',
                    ]);
                }

                $addQty = (int) ($d['add_qty'] ?? 1);
                $merged = false;

                foreach ($lines as &$line) {
                    if ($line['product_id'] === (int) $product->id) {
                        $line['qty'] += $addQty;
                        $merged = true;
                        break;
                    }
                }
                unset($line);

                if (! $merged) {
                    $lines[] = [
                        'item' => null,
                        'product_id' => (int) $product->id,
                        'name' => $product->name,
                        'price' => (float) $product->price,
                        'qty' => $addQty,
                    ];
                }
            }

            if ($lines === []) {
                throw ValidationException::withMessages([
                    'items' => 'An order must have at least one item.',
                ]);
            }

            $total = 0.0;
            foreach ($lines as $line) {
                $total += $line['price'] * $line['qty'];
            }

            $cash = null;
            $change = null;
            if ($d['method'] === 'Cash') {
                $cash = (float) ($d['cash'] ?? 0);
                if ($cash < $total) {
                    throw ValidationException::withMessages([
                        'cash' => 'Cash received is less than the new total of ₱' . number_format($total, 2) . '.',
                    ]);
                }
                $change = $cash - $total;
            }

            // Inventory reconciliation: return what is no longer used, deduct what was added.
            $productIds = $order->items->pluck('product_id')
                ->merge(collect($lines)->pluck('product_id'))
                ->filter()->unique();

            $products = Product::with('ingredients')
                ->whereIn('id', $productIds)
                ->get()->keyBy('id');

            $oldNeed = $this->ingredientNeeds($order->items, $products);
            $newNeed = $this->ingredientNeeds($lines, $products);

            $delta = [];
            foreach ($oldNeed as $id => $qty) {
                $delta[$id] = ($delta[$id] ?? 0) + $qty;
            }
            foreach ($newNeed as $id => $qty) {
                $delta[$id] = ($delta[$id] ?? 0) - $qty;
            }

            $ingredients = Ingredient::whereIn('id', array_keys($delta))
                ->lockForUpdate()->get()->keyBy('id');

            foreach ($delta as $id => $diff) {
                if ($diff >= 0) {
                    continue;
                }

                $ingredient = $ingredients[$id] ?? null;
                if (! $ingredient || (float) $ingredient->stock < -$diff) {
                    throw ValidationException::withMessages([
                        'items' => 'Not enough ' . ($ingredient->name ?? 'ingredient') . ' in stock for this change.',
                    ]);
                }
            }

            foreach ($delta as $id => $diff) {
                if (abs($diff) < 0.0001) {
                    continue;
                }

                $ingredient = $ingredients[$id];
                $before = (float) $ingredient->stock;
                $ingredient->stock = $before + $diff;
                $ingredient->save();

                StockMovement::create([
                    'ingredient_id' => $id,
                    'type' => $diff > 0 ? 'in' : 'used',
                    'quantity' => $diff,
                    'previous_stock' => $before,
                    'resulting_stock' => (float) $ingredient->stock,
                    'reason' => 'Order #' . $order->id . ' edited',
                    'user_id' => auth()->id(),
                ]);
            }

            // Apply item changes.
            $keptIds = [];
            foreach ($lines as $line) {
                if (! $line['item']) {
                    continue;
                }

                $item = $line['item'];
                if ((int) $item->qty !== $line['qty']) {
                    $item->qty = $line['qty'];
                    $item->save();
                }
                $keptIds[] = $item->id;
            }

            $order->items()->whereNotIn('id', $keptIds ?: [0])->delete();

            foreach ($lines as $line) {
                if ($line['item']) {
                    continue;
                }

                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'name' => $line['name'],
                    'price' => $line['price'],
                    'qty' => $line['qty'],
                ]);
            }

            $user = auth()->user();
            $order->update([
                'total' => $total,
                'payment_method' => $d['method'],
                'reference' => $d['method'] === 'Cash' ? null : ($d['reference'] ?? null),
                'cash_received' => $cash,
                'change_amount' => $change,
                'edited_at' => now(),
                'edited_by' => $user?->id,
                'edited_by_name' => $user?->name ?: 'Staff',
                'edit_reason' => $d['reason'],
            ]);
        });

        return redirect()->route('staff.history')
            ->with('ok', 'Order #' . $order->id . ' updated. The owner will see the edit reason in the Sales Report.');
    }

    public function destroy(Request $request, Order $order)
    {
        $d = $request->validate([
            'reason' => 'required|string|max:255',
        ], [
            'reason.required' => 'Please enter the reason for deleting this order.',
        ]);

        DB::transaction(function () use ($order, $d) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $order->load('items');

            // Return every ingredient used by this order to stock.
            $products = Product::with('ingredients')
                ->whereIn('id', $order->items->pluck('product_id')->filter()->unique())
                ->get()->keyBy('id');

            $needs = $this->ingredientNeeds($order->items, $products);
            $ingredients = Ingredient::whereIn('id', array_keys($needs))
                ->lockForUpdate()->get()->keyBy('id');

            foreach ($needs as $id => $qty) {
                if ($qty <= 0) {
                    continue;
                }

                $ingredient = $ingredients[$id] ?? null;
                if (! $ingredient) {
                    continue;
                }

                $before = (float) $ingredient->stock;
                $ingredient->stock = $before + $qty;
                $ingredient->save();

                StockMovement::create([
                    'ingredient_id' => $id,
                    'type' => 'in',
                    'quantity' => $qty,
                    'previous_stock' => $before,
                    'resulting_stock' => (float) $ingredient->stock,
                    'reason' => 'Order #' . $order->id . ' deleted',
                    'user_id' => auth()->id(),
                ]);
            }

            $user = auth()->user();
            $order->update([
                'deleted_by' => $user?->id,
                'deleted_by_name' => $user?->name ?: 'Staff',
                'delete_reason' => $d['reason'],
            ]);

            // Soft delete: the row stays in the database for the owner's audit report.
            $order->delete();
        });

        return back()->with('ok', 'Order #' . $order->id . ' deleted. Its ingredients were returned to stock and the owner can see it in the Sales Report.');
    }

    private function ingredientNeeds(iterable $lines, $products): array
    {
        $needs = [];

        foreach ($lines as $line) {
            $isArray = is_array($line);
            $productId = $isArray ? ($line['product_id'] ?? null) : $line->product_id;
            $qty = (int) ($isArray ? ($line['qty'] ?? 0) : $line->qty);

            if (! $productId || $qty < 1) {
                continue;
            }

            $product = $products[$productId] ?? null;
            if (! $product) {
                continue;
            }

            foreach ($product->ingredients as $ingredient) {
                $needs[$ingredient->id] = ($needs[$ingredient->id] ?? 0)
                    + (float) $ingredient->pivot->quantity * $qty;
            }
        }

        return $needs;
    }

    public function receipt(Order $order)
    {
        return view('staff.receipt', ['order' => $order->load('items', 'user')]);
    }
}
