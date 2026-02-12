// Sélection par défaut  de l'unité de réappro

$('#equipement').change(function () {
    let maxQte = $('#equipement option:selected').attr('lang');
    $('#qte').val('').attr('max', maxQte);
});

// Calcul du total de la ligne

$('#qte').keyup(function () {
    let qte = $(this).val();
    let max = $(this).attr('max');

    if(qte > max){
        $(this).val(max);
    }
});

// Click sur le bouton ajouter

$('#addToTable').click(function(){

    let equipement = $('#equipement').select2('data')[0];

    let qte = $('#qte').val();

    let valActuelle = $('.total').text();

    //console.log(intrant);

    if(equipement.text !== '' && qte > 0){

        tempInput = $('.int_'+ equipement.id).length;

        if(tempInput <= 0){

            let tempValActuelle = parseInt(valActuelle) + parseInt(qte);

            $('.total').text(tempValActuelle);

            td = "<td>"+equipement.text+"</td>";
            inputs = "<input name='equipement[]' type='hidden' value='"+equipement.id+"' />";

            td += "<td class='qte'>"+qte+"</td>";
            inputs += "<input name='qte[]' type='hidden' value='"+qte+"' />";

            td += "<td><button type='button' class='btn btn-danger removeBtn' id='"+equipement.id+"'> Retirer</button></td>";

            tr = "<tr id='tr_"+ equipement.id +"' class='int_"+ equipement.id +"'>"+td+"</tr>";
            elem = "<div id='elem_"+equipement.id +"'>"+ inputs +"</div>";

            $('#tbody').append(tr);
            $('#formulaire').append(elem);

            $('#qte').val("");
            $('#pu').val("");
        }else{
            alert("Equipement déjà ajouté. Veuillez le rétirer d'abord");
        }

        /*$('#produit option[value='+intrant.id+']').remove();*/
        $('#produit').trigger('change');
    }else{
        alert('Champs vide');
    }
});

$(document).on("click",".removeBtn",function() {
    let id = $(this).attr('id');

    valActuelle = $('.total').text();

    tempValActuelle = parseInt(valActuelle) - parseInt($('#tr_'+id+' .qte').text());

    $('.total').text(tempValActuelle);

    $('#tr_'+id).remove();
    $('#elem_'+id).remove();
});