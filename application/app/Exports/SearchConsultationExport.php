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


class SearchConsultationExport implements FromView, WithColumnFormatting, ShouldAutoSize, WithColumnWidths
{
    use Exportable;

    protected  $SaveSite, $SaveProjet, $SaveMedecin, $SaveContagieuse,
              $SaveCentreExtene,$SaveProf, $SaveTypeArret, $SaveAccidentPro, $SaveMotifConsul,
              $SaveMotifRejet, $SaveMedocAdm, $SaveTraitAdmin, $SaveCovid, $SaveTypeConsul, $SaveMedecinExterne, $SaveDateDebut, $SaveDateFin, $data;


    function __construct($SaveSite, $SaveProjet, $SaveMedecin, $SaveContagieuse,
    $SaveCentreExtene,$SaveProf, $SaveTypeArret, $SaveAccidentPro, $SaveMotifConsul,
    $SaveMotifRejet, $SaveMedocAdm, $SaveTraitAdmin, $SaveCovid, $SaveTypeConsul, $SaveMedecinExterne, $SaveDateDebut, $SaveDateFin) {
        $this->SaveSite = $SaveSite;
        $this->SaveMedecin = $SaveMedecin;
        $this->SaveProjet = $SaveProjet;
        $this->SaveContagieuse = $SaveContagieuse;
        $this->SaveProjet = $SaveProjet;
        $this->SaveCentreExtene = $SaveCentreExtene;
        $this->SaveProf = $SaveProf;
        $this->SaveMotifRejet = $SaveMotifRejet;
        $this->SaveTypeArret = $SaveTypeArret;
        $this->SaveAccidentPro = $SaveAccidentPro;
        $this->SaveMotifConsul = $SaveMotifConsul;
        $this->SaveMedocAdm = $SaveMedocAdm;
        $this->SaveTraitAdmin = $SaveTraitAdmin;
        $this->SaveCovid = $SaveCovid;
        $this->SaveTypeConsul = $SaveTypeConsul;
        $this->SaveMedecinExterne = $SaveMedecinExterne;
        $this->SaveDateDebut = $SaveDateDebut;
        $this->SaveDateFin = $SaveDateFin;
        $this->data =Consultation::all();


    }
    public function view(): View
    {

        if (isset($this->SaveSite)  ){
            $this->data =   $this->data->where('siteConsultation', '=', $this->SaveSite );
        }
        if (isset($this->SaveProjet) ){
            $this->data =  $this->data->where('projet_id', '=', $this->SaveProjet );
        }
        if (isset($this->SaveMedecin) ){
            $this->data =  $this->data->where('projet_id', '=', $this->SaveMedecin );
        }
        if (isset($this->SaveContagieuse) ){
            $this->data =  $this->data->where('maladie_contagieuse', '=', $this->SaveContagieuse );
        }
        if (isset($this->SaveCentreExtene) ){
            $this->data =  $this->data->where('designationCentreExterne', '=', $this->SaveCentreExtene );
        }
        if (isset($this->SaveProf) ){
            $this->data =  $this->data->where('maladie_prof', '=', $this->SaveProf );
        }
        if (isset($this->SaveTypeArret) ){
            $this->data =  $this->data->where('typeArrêt', '=', $this->SaveTypeArret );
        }
        if (isset($this->SaveAccidentPro) ){
            $this->data =  $this->data->where('accidentPro', '=', $this->SaveAccidentPro );
        }
        if (isset($this->SaveMotifConsul) ){
            $this->data =  $this->data->where('motif_consultation_id', '=', $this->SaveMotifConsul );
        }
        if (isset($this->SaveMotifRejet) ){
            $this->data =  $this->data->where('motifRejet', '=', $this->SaveMotifRejet );
        }
        if (isset($this->SaveMedocAdm) ){
            $this->data =  $this->data->where('soinadministre', '=', $this->SaveMedocAdm );
        }
        if (isset($this->SaveTraitAdmin) ){
            $this->data =  $this->data->where('traitementAdmin', '=', $this->SaveTraitAdmin );
        }

        if (isset($this->SaveCovid) ){
            $this->data =  $this->data->where('testCovid', '=', $this->SaveCovid );
        }

        if (isset($this->SaveTypeConsul) ){
            $this->data =  $this->data->where('typeConsultation', '=', $this->SaveTypeConsul );
        }

        if (isset($this->SaveMedecinExterne) ){
            $this->data =  $this->data->where('user_id', '=', $this->SaveMedecinExterne );
        }

        if (isset($this->SaveDateDebut)){
            $this->data =  $this->data->whereBetween('dateConsultation', [$this->SaveDateDebut, $this->SaveDateFin]);
        }


        return view('export.search', [
            'data' =>  $this->data,
            'datedebut' =>  $this->SaveDateDebut,
            'datefin' =>  $this->SaveDateFin
        ]);

    }

    public function columnFormats(): array
    {
        return [

            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'J' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 30,
            'C' => 45,
            'D' => 45,
            'E' => 25,
            'F' => 25,
            'G' => 45,
            'V'=> 70,
            'L'=> 45,
            'K'=> 45,
            'M'=> 45,
        ];
    }

}
