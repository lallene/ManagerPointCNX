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

class IrisDateExport implements FromView, WithColumnFormatting, ShouldAutoSize, WithColumnWidths
{

    use Exportable;
    protected  $SaveAgent, $SaveDateDebut, $SaveDateFin, $data, $iris, $name;


    function __construct($SaveAgent, $SaveDateDebut, $SaveDateFin, $iris,  $name) {

        $this->iris = $iris;
        $this->name =$name;
        $this->SaveDateDebut = $SaveDateDebut;
        $this->SaveDateFin = $SaveDateFin;
        $this->data =Consultation::where('agent_id', '=', $SaveAgent)->get();
        //dd($this->data);
    }


    public function view(): View
    {

        return view('export.iris', [
            'datas' =>  $this->data,
            'datedebut' =>  $this->SaveDateDebut,
            'datefin' =>  $this->SaveDateFin,
            'iris' => $this->iris,
            'name'=> $this->name
        ]);

    }

    public function columnFormats(): array
    {
        return [

            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'F' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY,

        ];
    }

    public function columnWidths(): array
    {
        return [
            'A'=>25,
            'C' => 45,
            'E' => 20,
            'D'=> 20,
            'K'=> 45,
            'J'=> 35,
            'I'=> 30,
            'M' => 20
        ];
    }


}
