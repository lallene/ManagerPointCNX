<?php

namespace App\Exports;

use App\Models\Consultation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;


class ConsultationDateExport implements FromView, WithColumnFormatting, ShouldAutoSize, WithColumnWidths
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $from, $to, $data;


    function __construct($from, $to) {

        $this->from = $from;
        $this->to = $to;
        $this->data =Consultation::all();
    }

    public function view(): View
    {



        if (isset($this->from) ){
            $this->data =  $this->data->whereBetween('dateConsultation', [$this->from,  $this->to])
                                      ;

        }

            return view('export.consultations', [
                    'invoices' =>  $this->data,
                    'datedebut' =>  $this->from,
                    'datefin' =>  $this->to,
        ]);

    }


    public function columnFormats(): array
    {
        return [


            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'J' => NumberFormat::FORMAT_DATE_DDMMYYYY


        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 17,
            'C' => 25,
            'D' => 50,
            'E' => 20,
            'F' => 20,
            'G' => 12,
            'H' => 30,
            'I' => 25,
            'J' => 20,
            'K' => 20,
            'L' => 45,

        ];
    }

}
