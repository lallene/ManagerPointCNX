@component('mail::message')
<style>
    *{
        font-family: "Poppins", sans-serif !important
    }
</style>

Bonjour,


L'arrêt de travail avec le statut ‘en attente de validation’ comportant les informations ci-dessous, a atteint sa période d'expiration car nous n'avons pas reçu les informations complémentaires demandées dans les délais prescrits.

Cet arrêt est donc automatiquement rejeté de notre système. L’absence correspondante n’est donc pas justifiée.


•        Iris du collaborateur concerné : <b>{{$agent->iris}}</b>

•        Prénoms, NOM du collaborateur concerné : <b> {{$agent->prenom}} {{$agent->nom}}</b>

•        Date de début de l’arrêt : <b> {{$dateDebut}}</b>

•        Nombre total de jours de l’arrêt :  <b>{{$nbreJour}}</b> jours.

•        Date de reprise du travail : <b> {{$dateReprise}}</b>.

•        Délivré par un médecin {{$consultation->typeConsultation}}  @if ($consultation->typeConsultation =='Externe') {{$consultation->nomMedecin}}  de l’établissement de santé <b>{{$consultation->designationCentreExterne}}</b> @endif

Pour toute information complémentaire, nous vous remercions de bien vouloir vous rapprocher de votre médecin d’entreprise ou votre HR Business Partner.<br>



Cordialement,<br>



Votre Team RH Côte d’Ivoire


@endcomponent


