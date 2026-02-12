// Sélection par défaut  de l'unité de réappro

$('#searchField').keyup(function () {
    let id = $(this).val();

    let url = $('#url').val()+id;

    params = new FormData();

    params.append( 'id', id);

    $('#tbody').html('');

    if(id !== "") {
        var config = {
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        };
        axios.post(url, params, config)
            .then(function (response) {

                let data = response.data;
                console.log(data);

                for (var i = 0; i < data.length; i++) {

                    td  = "<td>"+data[i].SN +"</td>";
                    td += "<td>"+data[i].CodeBarre +"</td>";
                    td += "<td>"+data[i].Equipement +"</td>";
                    td += "<td>"+data[i].Site +"</td>";
                    td += "<td>"+data[i].DateMiseEnService +"</td>";
                    td += "<td><input type='text' class='form-control'></td>";
                    td += "<td></td>";

                    tr = "<tr>"+td+"</tr>";

                    $('#tbody').append(tr);




                }
                $("#marque").trigger('change');
            })
            .catch(function (error) {
                console.log(error);
            });
    }else{
        $('#marque').html('');
    }
});

// Click sur le bouton ajouter

$('#addToTable').click(function(){

    let equipement = $('#equipement').select2('data')[0];

    let marque = $('#marque').select2('data')[0];

    let qte = $('#qte').val();

    let pu = $('#pu').val();

    let valActuelle = $('.total').text();

    //console.log(intrant);

    if(equipement.text !== '' && qte > 0 && pu >= 0 && marque.text !== 0){

        tempInput = $('.int_'+ equipement.id+'_'+ marque.id).length;

        if(tempInput <= 0){

            tempValActuelle = parseInt(valActuelle) + ( parseInt(qte) * parseInt(pu) );

            $('.total').text(tempValActuelle);

            td = "<td>"+equipement.text+"</td>";
            inputs = "<input name='equipement[]' type='hidden' value='"+equipement.id+"' />";

            td += "<td>"+marque.text+"</td>";
            inputs += "<input name='marque[]' type='hidden' value='"+marque.id+"' />";

            td += "<td>"+qte+"</td>";
            inputs += "<input name='qte[]' type='hidden' value='"+qte+"' />";

            td += "<td>"+pu+"</td>";
            inputs += "<input name='pu[]' type='hidden' value='"+pu+"' />";


            td += "<td class='tot'>"+parseInt(pu * qte)+"</td>";
            td += "<td><button type='button' class='btn btn-danger removeBtn' id='"+equipement.id+"'> Retirer</button></td>";

            tr = "<tr id='tr_"+ equipement.id +'_'+ marque.id +"' class='int_"+ equipement.id +'_'+ marque.id +"'>"+td+"</tr>";
            elem = "<div id='elem_"+equipement.id +'_'+ marque.id+"'>"+ inputs +"</div>";

            $('#tbody').append(tr);
            $('#formulaire').append(elem);

            $('#qte').val("");
            $('#pu').val("");
            $('#total').val("");
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

    tempValActuelle = parseInt(valActuelle) - parseInt($('#tr_'+id+' .tot').text());

    $('.total').text(tempValActuelle);

    $('#tr_'+id).remove();
    $('#elem_'+id).remove();
});