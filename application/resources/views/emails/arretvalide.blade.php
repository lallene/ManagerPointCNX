@component('mail::message')
<style>
    *{
        font-family: "Poppins", sans-serif !important;
        color: black !important ;
        text-align: justify;
    }
</style>

Bonjour,


Nous vous informons que <b> {{$agent->prenom}} {{$agent->nom}}</b> , <b>{{$agent->iris}}</b> du projet <b>  {{$agent->Projet->designation}}</b>  a remis, ce jour,  <b> {{ $dateConsul}}</b> un arrêt de travail validé pour cause de maladie .


    Vous trouverez ci-dessous les informations concernant cet arrêt :


•            Date de début :<b> {{$dateDebut}}</b>

•            Date de fin :<b> <?=  ($consultation->typeArrêt == "repos" ) ? \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($consultation->created_at->addMinutes($consultation->duree_arret)): ($consultation->debutArret == $consultation->dateReprise) ? date("d/m/Y ", strtotime($consultation->dateReprise)) : date("d/m/Y ", strtotime($consultation->dateReprise) - 1) ?></b>

•            Nombre d’heures de l’arrêt : <b><?= strlen(floor($consultation->duree_arret  / 60))  < 2   ? "0".floor($consultation->duree_arret  / 60) : floor($consultation->duree_arret  / 60) ?> : <?= strlen(($consultation->duree_arret  -   floor($consultation->duree_arret  / 60) * 60)) < 2 ? "0".($consultation->duree_arret  -   floor($consultation->duree_arret  / 60) * 60) : ($consultation->duree_arret  -   floor($consultation->duree_arret  / 60) * 60) ?></b>.

•            Date de reprise du travail :<b> {{$dateReprise}} </b>

•            Délivré par un médecin {{$consultation->typeConsultation}}  @if ($consultation->typeConsultation =='Externe') {{$consultation->nomMedecin}}  de l’établissement de santé <b>{{$consultation->designationCentreExterne}}</b> @else  <b> {{$consultation->Medecin->name}}</b>@endif



<i>Pour toute information complémentaire, nous vous remercions de bien vouloir vous rapprocher de votre médecin d’entreprise ou HR Business Partner.</i><br><br>



Cordialement,<br>
<b><h3>Votre Team RH Côte d'Ivoire</h3></b>


@endcomponent
