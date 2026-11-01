<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    const METHODS = ['Cash', 'GCash', 'Bank Transfer', 'Maya'];

    public function sales(Request $r)
    {
        return view('staff.sales', $this->data($r) + ['methods' => self::METHODS]);
    }

    // Excel-ready download (CSV opens directly in Excel)
    public function export(Request $r)
    {
        $d = $this->data($r);
        $name = 'sales-report-' . $d['from']->format('Y-m-d') . '-to-' . $d['to']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($d) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['DASH COFFEE - SALES REPORT']);
            fputcsv($f, ['Period', $d['from']->format('M d, Y') . ' - ' . $d['to']->format('M d, Y')]);
            fputcsv($f, ['Payment filter', $d['method'] ?: 'All']);
            fputcsv($f, []);
            fputcsv($f, ['SUMMARY']);
            fputcsv($f, ['Total sales', number_format($d['total'], 2, '.', '')]);
            fputcsv($f, ['Orders', $d['count']]);
            fputcsv($f, ['Average order', number_format($d['avg'], 2, '.', '')]);
            fputcsv($f, ['Items sold', $d['itemsSold']]);
            fputcsv($f, ['Peak hour', $d['peakLabel'] ?? '-']);
            fputcsv($f, ['Deleted orders', $d['deletedOrders']->count()]);
            fputcsv($f, []);
            fputcsv($f, ['DAILY SALES']);
            fputcsv($f, ['Date', 'Sales']);
            foreach ($d['daily'] as $day => $v) fputcsv($f, [$day, number_format($v, 2, '.', '')]);
            fputcsv($f, []);
            fputcsv($f, ['TOP-SELLING PRODUCTS']);
            fputcsv($f, ['Rank', 'Product', 'Quantity', 'Revenue']);
            foreach ($d['products'] as $i => $p) fputcsv($f, [$i + 1, $p->name, $p->qty, number_format($p->revenue, 2, '.', '')]);
            fputcsv($f, []);
            fputcsv($f, ['PAYMENT METHODS']);
            fputcsv($f, ['Method', 'Orders', 'Total']);
            foreach ($d['payments'] as $m => $p) fputcsv($f, [$m, $p['count'], number_format($p['total'], 2, '.', '')]);
            fputcsv($f, []);
            fputcsv($f, ['TRANSACTIONS']);
            fputcsv($f, ['Order #', 'Date/time', 'Items', 'Payment', 'Total', 'Status', 'Edited', 'Edited by', 'Edit reason', 'Edited at']);
            foreach ($d['orders'] as $o) {
                fputcsv($f, [
                    $o->id,
                    $o->created_at->format('Y-m-d H:i'),
                    $o->items->map(fn ($i) => $i->qty . 'x ' . $i->name)->implode('; '),
                    $o->payment_method,
                    number_format($o->total, 2, '.', ''),
                    $o->status,
                    $o->edited_at ? 'Yes' : 'No',
                    $o->edited_by_name,
                    $o->edit_reason,
                    $o->edited_at?->format('Y-m-d H:i'),
                ]);
            }
            fputcsv($f, []);
            fputcsv($f, ['DELETED ORDERS (AUDIT)']);
            fputcsv($f, ['Order #', 'Date/time', 'Items', 'Payment', 'Total', 'Deleted by', 'Delete reason', 'Deleted at']);
            foreach ($d['deletedOrders'] as $o) {
                fputcsv($f, [
                    $o->id,
                    $o->created_at->format('Y-m-d H:i'),
                    $o->items->map(fn ($i) => $i->qty . 'x ' . $i->name)->implode('; '),
                    $o->payment_method,
                    number_format($o->total, 2, '.', ''),
                    $o->deleted_by_name,
                    $o->delete_reason,
                    $o->deleted_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($f);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function data(Request $r): array
    {
        $to = $r->query('to') ? Carbon::parse($r->query('to')) : now();
        $from = $r->query('from') ? Carbon::parse($r->query('from')) : now()->startOfMonth();
        if ($from->gt($to)) [$from, $to] = [$to, $from];
        if ($from->diffInDays($to) > 365) $from = $to->copy()->subDays(365);
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $method = in_array($r->query('method'), self::METHODS) ? $r->query('method') : null;

        $orders = Order::with('items', 'user')->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->latest()->get();

        // Soft-deleted orders are excluded from all sales totals above (global scope),
        // but the owner still needs to see who deleted them and why.
        $deletedOrders = Order::onlyTrashed()->with('items', 'user')
            ->whereBetween('created_at', [$from, $to])
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->latest('deleted_at')->get();

        $total = (float) $orders->sum('total');
        $count = $orders->count();

        // Daily sales across the chosen dates
        $daily = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) $daily[$d->format('Y-m-d')] = 0.0;
        foreach ($orders as $o) $daily[$o->created_at->format('Y-m-d')] += (float) $o->total;

        // Monthly sales for the year of the end date
        $monthly = array_fill(1, 12, 0.0);
        Order::where('status', 'completed')->whereYear('created_at', $to->year)
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->get(['total', 'created_at'])
            ->each(function ($o) use (&$monthly) { $monthly[$o->created_at->month] += (float) $o->total; });

        // Peak hours
        $hCount = array_fill(0, 24, 0);
        $hSales = array_fill(0, 24, 0.0);
        foreach ($orders as $o) { $h = $o->created_at->hour; $hCount[$h]++; $hSales[$h] += (float) $o->total; }
        $peak = $count ? array_search(max($hCount), $hCount) : null;
        $peakLabel = $peak === null ? null : Carbon::createFromTime($peak)->format('g A') . ' - ' . Carbon::createFromTime(($peak + 1) % 24)->format('g A');

        $products = $orders->flatMap(fn ($o) => $o->items)->groupBy('name')
            ->map(fn ($g, $name) => (object) ['name' => $name, 'qty' => $g->sum('qty'), 'revenue' => $g->sum(fn ($i) => $i->price * $i->qty)])
            ->sortByDesc('revenue')->values()->take(10);

        $payments = $orders->groupBy('payment_method')
            ->map(fn ($g) => ['count' => $g->count(), 'total' => (float) $g->sum('total')]);

        $alerts = Ingredient::whereColumn('stock', '<=', 'low_level')->orderBy('stock')->get();

        return [
            'from' => $from, 'to' => $to, 'method' => $method, 'orders' => $orders,
            'deletedOrders' => $deletedOrders,
            'total' => $total, 'count' => $count, 'avg' => $count ? $total / $count : 0,
            'itemsSold' => $orders->flatMap(fn ($o) => $o->items)->sum('qty'),
            'daily' => $daily, 'monthly' => $monthly, 'hCount' => $hCount, 'hSales' => $hSales,
            'peakLabel' => $peakLabel, 'products' => $products, 'payments' => $payments,
            'out' => $alerts->filter(fn ($i) => (float) $i->stock <= 0),
            'low' => $alerts->filter(fn ($i) => (float) $i->stock > 0),
        ];
    }
}
