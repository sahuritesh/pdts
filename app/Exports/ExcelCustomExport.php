<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelCustomExport implements FromCollection,WithHeadings,WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $data;
    protected $headings;
    public function __construct(array $data,array $headings){
        $this->data = $data;
        $this->headings =  $headings;
    }
    
    public function collection(){
        return collect($this->data);
    }

    public function headings(): array 
    {
        return $this->headings;
    }


    public function styles(Worksheet $sheet)
    {
        // Apply borders to the entire table range
        $sheet->getStyle('A1')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);
    }
}
