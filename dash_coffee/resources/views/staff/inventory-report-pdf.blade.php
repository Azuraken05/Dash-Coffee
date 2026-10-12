<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <style>
    @page { margin: 28px 32px 48px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #18241b; }
    .head { width: 100%; border-bottom: 3px solid #2f6b3a; padding-bottom: 8px; margin-bottom: 12px; }
    .head td { vertical-align: middle; }
    .logo { width: 54px; height: 54px; }
    h1 { margin: 0; font-size: 18px; color: #2f6b3a; }
    .meta { color: #5b645d; font-size: 9px; margin-top: 3px; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #2f6b3a; color: #fff; padding: 6px 7px; text-align: left; font-size: 9px; }
    table.data td { padding: 5px 7px; border-bottom: 1px solid #dfe5da; }
    table.data tr:nth-child(even) td { background: #f5f8f2; }
    .num { text-align: right; }
    .sum { margin-top: 14px; width: 50%; border-collapse: collapse; }
    .sum td { padding: 4px 7px; border: 1px solid #dfe5da; }
    .sum td:first-child { background: #eef6ea; font-weight: bold; }
    .sign { margin-top: 38px; width: 100%; }
    .sign td { width: 50%; padding-top: 4px; border-top: 1px solid #333; text-align: center; font-size: 9px; }
    .sign td.gap { border: 0; width: 8%; }
  </style>
</head>
<body>
  <table class="head"><tr>
    <td style="width:62px"><img class="logo" src="{{ public_path('images/dashcoffee-logo.jpg') }}" /></td>
    <td><h1>DASH COFFEE — {{ $title }}</h1>
      <div class="meta">Period: {{ $period }}@if ($type === 'current') · Filter: {{ ['all' => 'All ingredients', 'low' => 'Low stock', 'out' => 'Out of stock'][$status] }}@endif</div>
      <div class="meta">Generated: {{ now()->format('M d, Y g:i A') }}</div></td>
  </tr></table>

  <table class="data">
    <tr>@foreach ($headings as $h)<th>{{ $h }}</th>@endforeach</tr>
    @foreach ($rows as $row)<tr>@foreach ($row as $cell)<td class="{{ is_numeric($cell) ? 'num' : '' }}">{{ $cell }}</td>@endforeach</tr>@endforeach
  </table>
  @if (count($rows) === 0)<p>No records for these filters.</p>@endif

  <table class="sum">@foreach ($summary as $label => $value)<tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>@endforeach</table>

  <table class="sign"><tr><td>Prepared by</td><td class="gap"></td><td>Checked / approved by</td></tr></table>

  <script type="text/php">
    if (isset($pdf)) {
      $font = $fontMetrics->getFont('helvetica');
      $pdf->page_text($pdf->get_width() - 110, $pdf->get_height() - 28, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 8, [0.4, 0.4, 0.4]);
    }
  </script>
</body>
</html>
