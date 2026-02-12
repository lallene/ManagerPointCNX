@component('mail::message')
<style>
    *{
        font-family: "Poppins", sans-serif !important
    }
</style>

Bonjour Setushi,


Nous vous informons que <b> {{$agent->prenom}} {{$agent->nom}}</b> , <b>{{$agent->iris}}</b> du projet <b>  {{$agent->Projet->designation}}</b>  a reçu/remis, ce jour,  <b> {{ $dateConsul}}</b> un arrêt de travail pour cause de maladie.


@if( $consultation->etatValidite == 'valide')

    Vous trouverez ci-dessous les informations concernant cet arrêt :


•            Date de début :<b> {{$dateDebut}}</b>

•            Date de fin :<b> {{$dateFin}}</b>

•            Nombre total de jours de l’arrêt : <b>{{$nbreJour}}</b> jours.

•            Date de reprise du travail :<b> {{$dateReprise}}</b>

•            Délivré par un médecin <b>{{$consultation->typeConsultation}}</b>   @if(($consultation->typeConsultation =='Externe')){ de l’établissement de santé <b>{{$consultation->designationCentreExterne}}</b>}



<i>Pour toute information complémentaire, nous vous remercions de bien vouloir vous rapprocher de votre médecin d’entreprise ou HR Business Partner.</i><br><br>



Cordialement,<br>
<b><h3>Votre Team RH Côte d'Ivoire</h3></b>

@elseif ( $consultation->arretMaladie =='repos')

Vous trouverez ci-dessous les informations concernant cet arrêt :


•            Date de début :<b> {{$dateDebut}}</b>

•            Date de fin :<b> <?=  ($consultation->typeArrêt == "repos" ) ? \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($consultation->created_at->addMinutes($consultation->duree_arret)): ($consultation->debutArret == $consultation->dateReprise) ? date("d/m/Y ", strtotime($consultation->dateReprise)) : date("d/m/Y ", strtotime($consultation->dateReprise) - 1) ?></b>

•            Nombre d’heures de l’arrêt : <b><?= strlen(floor($consultation->duree_arret  / 60))  < 2   ? "0".floor($consultation->duree_arret  / 60) : floor($consultation->duree_arret  / 60) ?> : <?= strlen(($consultation->duree_arret  -   floor($consultation->duree_arret  / 60) * 60)) < 2 ? "0".($consultation->duree_arret  -   floor($consultation->duree_arret  / 60) * 60) : ($consultation->duree_arret  -   floor($consultation->duree_arret  / 60) * 60) ?></b>.

•            Date de reprise du travail :<b> {{$dateReprise}}</b>

•            Délivré par un médecin <b>{{$consultation->typeConsultation}}</b>



<i>Pour toute information complémentaire, nous vous remercions de bien vouloir vous rapprocher de votre médecin d’entreprise ou HR Business Partner.</i><br><br>



Cordialement,<br>
<b><h3>Votre Team RH Côte d'Ivoire</h3></b>



@else

 L'arrêt maladie est rejeté pour motif<b> {{$consultation->motifRejet}} </b>.

<i>Pour toute information complémentaire, nous vous remercions de bien vouloir vous rapprocher de votre médecin d’entreprise ou HR Business Partner.</i><br><br>
Cordialement,<br>

<b><h3>Votre Team RH Côte d'Ivoire</h3></b>

@endif


@endcomponent
