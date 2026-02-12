<?php

namespace App\Exports;
use App\Models\Consultation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class IrisExport implements  FromView
{
    use Exportable;

    protected $from, $to, $iris;
    function __construct($from, $to, $iris) {
        $this->from = $from;
        $this->to = $to;
        $this->iris = $iris;
 }

    public function view(): View
    {
        return view('export.iris', [
            'invoices' => Consultation::all()->whereBetween('dateConsultation', [$this->from, $this->to])
                                             ->where('agent_id', '=' ,  $this->iris),
            'datedebut' =>  $this->from,
            'datefin' =>  $this->to
        ]);
    }
}
