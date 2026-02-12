<?php

namespace App\Exports;

use App\Models\Consultation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;



use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
class ConsultationsExport implements FromView, WithColumnFormatting, ShouldAutoSize, WithColumnWidths
{
    use Exportable;

    protected $from, $to;


    function __construct($from, $to) {
        $this->from = $from;
        $this->to = $to;
    }

    public function view(): View
    {

            return view('export.consultations', [
            'invoices' => Consultation::all()->whereBetween('dateConsultation', [$this->from, $this->to])

                                             ,
            'datedebut' =>  $this->from,
            'datefin' =>  $this->to
        ]);

    }


    public function columnFormats(): array
    {
        return [

            'J' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 40,
            'D' => 55,
            'E' => 17,
            'F' => 20,
            'G' => 30,
            'H' => 25,
            'I' => 25,
            'j' => 25,
            'k' => 50,
        ];
    }

}
