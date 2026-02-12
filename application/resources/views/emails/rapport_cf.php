@component('mail::message')
<style>
    *{
        font-family: "Poppins", sans-serif !important
    }
</style>

Bonjour {{$cf->nom}},

DATE :        {{$datedebutexport}}            DU             {{$datefinexport}}

            NOM DU PROJET : {{$projetselected }}

<fieldset class="mt-2">
    <legend>Liste des arrêts</legend>
    <table class="table table-striped table-responsive-sm table-bordered">
        <thead>
            <tr>
                <th width="20%">IRIS</th>
                <th width="30%">NOM-PRENOM</th>
                <th width="20%">TYPE D'ARRET</th>
                <th width="30%">DUREE</th>
                <th width="30%">DATE D'ENREGISTREMENT</th>
                <th width="30%">DATE DE DEBUT</th>
                <th width="30%">DATE DE FIN</th>
                <th width="30%">DATE DE REPRIS</th>
            </tr>
            </thead>
        <tbody>
        <?php
            if(isset($consultation) AND !empty($consultation)){
                foreach ($consultation as $cs) {
                    ?>
                        <tr>
                            <td><?= $cs->Agent->iris ?></td>
                            <td><?= $cs->Agent->nom.' '.$cs->Agent->prenom  ?></td>
                            <td><?= $cs->typeArrêt ?></td>
                            <td><?= $cs->duree_arret ?> min</td>
                            <td><?=  date('d-m-Y', strtotime($cs->created_at)) ?></td>
                            <td><?=  date('d-m-Y', strtotime($cs->debutArret)) ?></td>
                            <td><?=  date('d-m-Y', strtotime($cs->dateReprise. ' - 1 days')) ?></td>
                            <td><?=  date('d-m-Y', strtotime($cs->dateReprise)) ?></td>
                        </tr>
                    <?php
                }
            }
        ?>
        </tbody>
    </table>
</fieldset>



<i>Pour toute information complémentaire, nous vous remercions de bien vouloir vous rapprocher de votre médecin d’entreprise ou HR Business Partner.</i><br><br>



Cordialement,<br>
<b><h3>Votre Team RH Côte d'Ivoire</h3></b>


@endcomponent
