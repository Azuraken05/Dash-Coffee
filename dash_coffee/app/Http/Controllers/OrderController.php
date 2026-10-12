<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function create()
    {
        return view('staff.orders', [
            'products' => Product::with('ingredients')->where('status', '!=', 'hidden')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'integer|min:1',
            'method' => 'required|in:Cash,GCash,Back Transfer,Maya',
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

        return redirect()->route('staff.receipt', $order);
    }

    public function history()
    {
        return view('staff.history', [
            'orders' => Order::with('items', 'user')->latest()->paginate(15),
        ]);
    }

    public function receipt(Order $order)
    {
        return view('staff.receipt', ['order' => $order->load('items', 'user')]);
    }
}
