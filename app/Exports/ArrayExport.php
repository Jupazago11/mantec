<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ArrayExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $options = [],
    ) {
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $columnCount = count($this->headings);
                $rowCount = count($this->rows) + 1;

                if ($columnCount === 0) {
                    return;
                }

                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
                $fullRange = "A1:{$lastColumn}{$rowCount}";
                $headerRange = "A1:{$lastColumn}1";

                $sheet->getStyle($fullRange)->getFont()->setName('Calibri')->setSize(11);
                $sheet->getStyle($fullRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FF000000'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFB7B7B7'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight($this->options['header_row_height'] ?? 34);
                $sheet->freezePane($this->options['freeze_pane'] ?? 'A2');
                $sheet->setAutoFilter($fullRange);

                foreach (($this->options['column_widths'] ?? []) as $columnIndex => $width) {
                    $letter = Coordinate::stringFromColumnIndex((int) $columnIndex);
                    $sheet->getColumnDimension($letter)->setAutoSize(false);
                    $sheet->getColumnDimension($letter)->setWidth((float) $width);
                }

                foreach (($this->options['wrap_columns'] ?? []) as $columnIndex) {
                    $letter = Coordinate::stringFromColumnIndex((int) $columnIndex);
                    $sheet->getStyle("{$letter}1:{$letter}{$rowCount}")
                        ->getAlignment()
                        ->setWrapText(true);
                }

                foreach (($this->options['center_columns'] ?? []) as $columnIndex) {
                    $letter = Coordinate::stringFromColumnIndex((int) $columnIndex);
                    $sheet->getStyle("{$letter}1:{$letter}{$rowCount}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach (($this->options['cell_styles'] ?? []) as $cellStyle) {
                    $row = (int) ($cellStyle['row'] ?? 0);
                    $column = (int) ($cellStyle['column'] ?? 0);

                    if ($row < 2 || $column < 1) {
                        continue;
                    }

                    $coordinate = Coordinate::stringFromColumnIndex($column) . $row;
                    $style = $sheet->getStyle($coordinate);

                    if (!empty($cellStyle['fill'])) {
                        $style->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB($cellStyle['fill']);
                    }

                    if (!empty($cellStyle['font_color'])) {
                        $style->getFont()->getColor()->setARGB($cellStyle['font_color']);
                    }

                    if (array_key_exists('bold', $cellStyle)) {
                        $style->getFont()->setBold((bool) $cellStyle['bold']);
                    }

                    if (!empty($cellStyle['horizontal'])) {
                        $style->getAlignment()->setHorizontal($cellStyle['horizontal']);
                    }
                }
            },
        ];
    }
}
