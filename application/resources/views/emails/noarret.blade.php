@component('mail::message')
<style>
    *{
        font-family: "Poppins", sans-serif !important
    }
</style>

Bonjour,


Nous vous informons que <b> {{$agent->prenom}} {{$agent->nom}}</b> , <b>{{$agent->iris}}</b> du projet <b>  {{$agent->Projet->designation}}</b>  n'a pas recu d'arrêt de travail.

<b> Date et heure de fin de consultation :{{ $consultation->created_at}}</b>.



<i>Pour toute information complémentaire, nous vous remercions de bien vouloir vous rapprocher de votre médecin d’entreprise ou HR Business Partner.</i><br><br>
Cordialement,<br>

<b><h3>Votre Team RH Côte d'Ivoire</h3></b>

@endcomponent


