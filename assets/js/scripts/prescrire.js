
// Click sur le bouton Ajouter a l'ordonnance

$('#prescrire').click(function(){

    console.log('ok');
    let typeMedicament = $('#typeMedicamentPrescrit').val();

    let medicament = $('#medicamentPrescrit').val();

    let medicament2 = medicament.replace(' ', '');

    let qte = $('#qteMedoc').val();

    let nbrJrs = $('#nbrJrsPrescription').val();

    let inputs = '';

   

    if( medicament !== '' && qte > 0 && nbrJrs > 0){

        tempInput = $('.int_'+ typeMedicament +'_'+medicament).length;

        if(tempInput <= 0){

            td = "<td>"+typeMedicament+"</td>";
            inputs += "<input name='typeMedicament[]' type='hidden' value='"+typeMedicament+"' />";

            td += "<td>"+medicament+"</td>";
            inputs += "<input name='natureMedicament[]' type='hidden' value='"+medicament+"' />";

            td += "<td class='qte'>"+qte+"</td>";
            inputs += "<input name='qte[]' type='hidden' value='"+qte+"' />";

            td += "<td>"+nbrJrs+"</td>";
            inputs += "<input name='joursTraitement[]' type='hidden' value='"+nbrJrs+"' />";

            td += "<td><button type='button' class='btn btn-danger removeBtn btn-sm' id='"+typeMedicament+"_"+medicament2+"'> <i class='fa fa-trash'></i></button></td>";

            tr = "<tr id='tr_"+ typeMedicament+"_"+medicament2 +"' class='int_"+ typeMedicament+"_"+medicament2 +"'>"+td+"</tr>";
            elem = "<div id='elem_"+typeMedicament+"_"+medicament2 +"'>"+ inputs +"</div>";

            $('#tbody').append(tr);
            $('#formulaire').append(elem);

            $('#medicamentPrescrit').val("");
            $('#qteMedoc').val("");
            $('#nbrJrsPrescription').val("");
        }else{
            alert("Médicament déjà ajouté. Veuillez le rétirer d'abord");
        }

        /*$('#produit option[value='+intrant.id+']').remove();*/
        //$('#produit').trigger('change');
    }else{
        alert('Champs vide');
    }
});

$(document).on("click",".removeBtn",function() {
    let id = $(this).attr('id');

    $('#tr_'+id).remove();
    $('#elem_'+id).remove();
});