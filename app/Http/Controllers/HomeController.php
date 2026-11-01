<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
    {
        $top = collect();

        // Until the products / order_items tables exist, this is skipped and the
        // homepage simply shows its built-in static content (nothing breaks).
        if (class_exists(Product::class) && Schema::hasTable('products') && Schema::hasTable('order_items')) {
            try {
                $top = Product::where('status', 'available')
                    ->withSum(['orderItems as sold' => fn ($q) =>
                        $q->whereHas('order', fn ($o) => $o->where('status', 'completed'))
                    ], 'quantity')
                    ->orderByDesc('sold')
                    ->take(7)
                    ->get()
                    ->filter(fn ($p) => $p->sold > 0)
                    ->values();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('homepage', [
            'signature' => $top->first(),
            'featured'  => $top->slice(1, 6),
        ]);
    }
}