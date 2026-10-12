<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private array $d)
    {
    }

    public function title(): string
    {
        return substr(str_replace(['&', '/'], '', $this->d['short']), 0, 31);
    }

    public function array(): array
    {
        $rows = [
            ['DASH COFFEE — ' . $this->d['title']],
            ['Period: ' . $this->d['period']],
            ['Generated: ' . now()->format('M d, Y g:i A')],
            [],
            $this->d['headings'], // row 5
        ];
        foreach ($this->d['rows'] as $r)
            $rows[] = $r;
        $rows[] = [];
        $rows[] = ['SUMMARY'];
        foreach ($this->d['summary'] as $k => $v)
            $rows[] = [$k, $v];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $last = Coordinate::stringFromColumnIndex(count($this->d['headings']));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A5:{$last}5")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2F6B3A']],
        ]);
        $sheet->setAutoFilter("A5:{$last}5");
        $sheet->freezePane('A6');

        return [];
    }
}
