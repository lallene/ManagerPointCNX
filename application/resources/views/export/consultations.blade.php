<style>
    table{
        font-family: Arial;
         font-size: 10px;
         text-align: justify;
    }
</style>
<table style="color:brown; font-weight: 900;"  >
    <thead>
    <tr>
        <th style="font-weight: 900;  " >Extraction du  {{date('r')}}  </th>
    </tr>
    <tr>
        <th style=" font-weight: 900; background-color :gray; font-family:Arial, Helvetica, sans-serif; ">SITE</th>
        <th style=" font-weight: 900; background-color :gray; font-family:Arial, Helvetica, sans-serif; ">ID WORKDAY</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">PROJET</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">NOM-PRENOM</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">TYPE D'ARRET</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">DUREE</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">DATE D'ENREGISTREMENT</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">DATE DE DEBUT</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">DATE DE FIN</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">DATE DE REPRIS</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">SITE DE CONSULTATION</th>
        <th style=" font-weight: 900;  background-color :gray; font-family:Arial, Helvetica, sans-serif; ">EMAIL</th>


    </tr>
    </thead>
    <tbody>
    @foreach($invoices as $invoice)
        <tr>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= $invoice->Agent->Societe->designation ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= ceil($invoice->Agent->Matricule_salarie)?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= $invoice->Projet->designation ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= $invoice->Agent->nom.' '.$invoice->Agent->prenom  ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= $invoice->typeArrêt === "oui"? "arret maladie" : $invoice->typeArrêt ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= strlen(floor($invoice->duree_arret  / 60))  < 2   ? "0".floor($invoice->duree_arret  / 60) : floor($invoice->duree_arret  / 60) ?> : <?= strlen(($invoice->duree_arret  -   floor($invoice->duree_arret  / 60) * 60)) < 2 ? "0".($invoice->duree_arret  -   floor($invoice->duree_arret  / 60) * 60) : ($invoice->duree_arret  -   floor($invoice->duree_arret  / 60) * 60) ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?=  \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($invoice->created_at) ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= ($invoice->typeArrêt== "repos" or $invoice->typeArrêt == "Analyse externe") ? \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($invoice->created_at) :date("d/m/Y ", strtotime($invoice->debutArret))?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?=  ($invoice->typeArrêt == "repos" ) ? \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($invoice->created_at->addMinutes($invoice->duree_arret)): ($invoice->debutArret == $invoice->dateReprise) ? date("d/m/Y ", strtotime($invoice->dateReprise)) : date("d/m/Y ", strtotime($invoice->dateReprise) - 1) ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= ($invoice->typeArrêt == "repos" ) ? \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($invoice->created_at->addMinutes($invoice->duree_arret)) : date("d/m/Y ", strtotime($invoice->dateReprise)) ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= $invoice->siteConsultation ?></td>
            <td style=" font-weight: normal;  text-align:justify; font-family:Arial, Helvetica, sans-serif;  "><?= $invoice->Agent->work_email ?></td>

        </tr>
    @endforeach
    </tbody>
</table>





