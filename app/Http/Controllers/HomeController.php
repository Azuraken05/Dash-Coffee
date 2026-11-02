<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;

class HomeController extends Controller
{
    // Orders with these statuses don't count as sales.
    private const NOT_COUNTED = ['cancelled', 'canceled', 'voided', 'void', 'refunded'];

    // false = only hide products you set to Hidden / Out of Stock yourself (Manage Menu).
    // true  = also hide products whose ingredients are too low to make them (same rule as displayStatus()).
    private const CHECK_INGREDIENT_STOCK = false;

    public function index()
    {
        $signature = null;
        $featured  = collect();

        try {
            // Total quantity sold per product (all time). Deleted / cancelled orders are skipped.
            // To use "last 30 days" instead, add: ->where('orders.created_at', '>=', now()->subDays(30))
            $sold = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereNull('orders.deleted_at')
                ->where(fn ($q) => $q->whereNull('orders.status')
                    ->orWhereNotIn('orders.status', self::NOT_COUNTED))
                ->whereNotNull('order_items.product_id')
                ->groupBy('order_items.product_id')
                ->selectRaw('order_items.product_id, SUM(order_items.qty) as sold')
                ->orderByDesc('sold')
                ->limit(30)
                ->pluck('sold', 'product_id');

            if ($sold->isNotEmpty()) {
                // Only products that are set to Available in Manage Menu.
                $available = Product::with('ingredients')->get()
                    ->filter(fn ($p) => self::CHECK_INGREDIENT_STOCK
                        ? $p->displayStatus() === 'available'
                        : $p->status === 'available');

                $ranked = $available
                    ->filter(fn ($p) => $sold->has($p->id))
                    ->sortByDesc(fn ($p) => (float) $sold[$p->id])
                    ->values();

                $signature = $ranked->first();

                $featured = $ranked->slice(1, 6)->values();
                $featured->each(fn ($p) => $p->badge =
                    $p->created_at && $p->created_at->gt(now()->subDays(30)) ? 'New' : 'Popular');

                // Not enough best sellers yet? Fill the rest with other available products.
                if ($featured->count() < 6) {
                    $extra = $available
                        ->reject(fn ($p) => $ranked->contains('id', $p->id))
                        ->sortByDesc('created_at')
                        ->take(6 - $featured->count())
                        ->values();
                    $extra->each(fn ($p) => $p->badge = 'Menu pick');
                    $featured = $featured->concat($extra)->values();
                }
            }
        } catch (\Throwable $e) {
            report($e);   // logged in storage/logs, but the homepage still loads
        }

        return view('homepage', [
            'signature' => $signature,
            'featured'  => $featured,
        ]);
    }
}