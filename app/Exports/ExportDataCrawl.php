<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Excel;

class ExportDataCrawl implements FromArray, WithStrictNullComparison
{
    use Exportable;

    public $data;
    public $header;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // private $writerType = Excel::CSV;

    // public function headings(): array
    // {
    //     return array(mb_convert_encoding($this->header,"SJIS", "UTF-8"));
    // }

    public function array(): array
    {
        return $this->data;
    }
}
