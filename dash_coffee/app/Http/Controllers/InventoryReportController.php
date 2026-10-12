<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
    const TYPES = [
        'current' => 'Current Inventory',
        'movements' => 'Stock Movements',
        'alerts' => 'Low & Out-of-Stock',
        'usage' => 'Inventory Usage',
    ];

    public function index(Request $r)
    {
        return view('staff.inventory-report', $this->report($r));
    }

    public function xlsx(Request $r)
    {
        $d = $this->report($r);

        return Excel::download(new ReportExport($d), $d['file'] . '.xlsx');
    }

    public function pdf(Request $r)
    {
        $d = $this->report($r);

        return Pdf::loadView('staff.inventory-report-pdf', $d)
            ->setPaper('a4', $d['landscape'] ? 'landscape' : 'portrait')
            ->download($d['file'] . '.pdf');
    }

    // One dataset used by the page, the Excel file and the PDF, so all three always match.
    private function report(Request $r): array
    {
        $type = array_key_exists($r->query('type'), self::TYPES) ? $r->query('type') : 'current';
        $to = $r->query('to') ? Carbon::parse($r->query('to')) : now();
        $from = $r->query('from') ? Carbon::parse($r->query('from')) : now()->subDays(29);
        if ($from->gt($to)) [$from, $to] = [$to, $from];
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $status = in_array($r->query('status'), ['low', 'out']) ? $r->query('status') : 'all';

        $st = fn ($i) => (float) $i->stock <= 0 ? 'Out of stock' : ($i->isLow() ? 'Low' : 'In stock');
        $num = fn ($v) => round((float) $v, 2);
        $dated = in_array($type, ['movements', 'usage']);
        $labels = ['in' => 'Stock In', 'out' => 'Stock Out', 'used' => 'Used'];

        if ($type === 'current') {
            $items = Ingredient::orderBy('name')->get()->filter(fn ($i) => match ($status) {
                'out' => (float) $i->stock <= 0,
                'low' => (float) $i->stock > 0 && $i->isLow(),
                default => true,
            });
            $headings = ['Ingredient', 'Unit', 'Current stock', 'Minimum stock', 'Status'];
            $rows = $items->map(fn ($i) => [$i->name, $i->unit, $num($i->stock), $num($i->low_level), $st($i)])->values()->all();
            $summary = [
                'Ingredients listed' => $items->count(),
                'Low stock' => $items->filter(fn ($i) => (float) $i->stock > 0 && $i->isLow())->count(),
                'Out of stock' => $items->filter(fn ($i) => (float) $i->stock <= 0)->count(),
            ];
        } elseif ($type === 'movements') {
            $items = StockMovement::with('ingredient', 'user')->whereBetween('created_at', [$from, $to])->latest()->get();
            $headings = ['Date / time', 'Ingredient', 'Movement', 'Quantity', 'Before', 'After', 'Reason', 'Staff'];
            $rows = $items->map(fn ($m) => [
                $m->created_at->format('M d, Y g:i A'), $m->ingredient->name, $labels[$m->type] ?? $m->type,
                $num($m->quantity), $m->previous_stock !== null ? $num($m->previous_stock) : '—',
                $m->resulting_stock !== null ? $num($m->resulting_stock) : '—', $m->reason, $m->user->name ?? '—',
            ])->all();
            $summary = [
                'Movements' => $items->count(),
                'Stock in / additions' => $items->where('quantity', '>', 0)->count(),
                'Deductions / usage' => $items->where('quantity', '<', 0)->count(),
            ];
        } elseif ($type === 'alerts') {
            $items = Ingredient::whereColumn('stock', '<=', 'low_level')->orderBy('stock')->get();
            $headings = ['Ingredient', 'Unit', 'Current stock', 'Minimum stock', 'Status', 'Needed to reach minimum'];
            $rows = $items->map(fn ($i) => [$i->name, $i->unit, $num($i->stock), $num($i->low_level), $st($i), $num(max((float) $i->low_level - (float) $i->stock, 0))])->all();
            $summary = [
                'Needs attention' => $items->count(),
                'Out of stock' => $items->filter(fn ($i) => (float) $i->stock <= 0)->count(),
                'Low stock' => $items->filter(fn ($i) => (float) $i->stock > 0)->count(),
            ];
        } else {
            $items = StockMovement::with('ingredient')->where('type', 'used')->whereBetween('created_at', [$from, $to])
                ->selectRaw('ingredient_id, SUM(-quantity) as used, COUNT(*) as uses')->groupBy('ingredient_id')->get()
                ->sortByDesc('used')->values();
            $headings = ['Ingredient', 'Unit', 'Total used', 'Times used (orders)'];
            $rows = $items->map(fn ($m) => [$m->ingredient->name, $m->ingredient->unit, $num($m->used), (int) $m->uses])->all();
            $summary = ['Ingredients used' => $items->count(), 'Usage records' => (int) $items->sum('uses')];
        }

        return [
            'type' => $type, 'types' => self::TYPES, 'title' => self::TYPES[$type] . ' Report',
            'short' => self::TYPES[$type], 'from' => $from, 'to' => $to, 'status' => $status, 'dated' => $dated,
            'period' => $dated ? $from->format('M d, Y') . ' – ' . $to->format('M d, Y') : 'As of ' . now()->format('M d, Y g:i A'),
            'headings' => $headings, 'rows' => $rows, 'summary' => $summary,
            'file' => 'inventory-' . $type . '-' . now()->format('Ymd-Hi'),
            'landscape' => count($headings) > 5,
        ];
    }
}
