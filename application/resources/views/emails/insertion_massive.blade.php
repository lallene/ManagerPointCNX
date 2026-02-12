@component('mail::message')
<style>
    *{
        font-family: "Poppins", sans-serif !important
    }
</style>

Bonjour,


Nous vous informons que l'insertion des  {{ $nbreColllaborateur}} collaborateurs a bien été inseré, à ce jour,  <b> {{ $dateInsertion}}.



Cordialement,<br>
<b><h3>Votre Team RH Côte d'Ivoire</h3></b>


@endcomponent
