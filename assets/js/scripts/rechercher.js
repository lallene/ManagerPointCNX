$('#searchIris').click(function (e) {
    e.preventDefault();
   console.log('tes');
    $('#demarrer').attr('disabled', 'disabled');
    $('#demarrer').attr('href', '#');

    $('#reception').attr('disabled', 'disabled');
    $('#reception').attr('href', '#');

    $('#nom').val('');
    $('#prenom').val('');
    $('#sexe').val('');
    $('#dateEmbauche').val('');
    $('#projet').val('');
    $('#fonction').val('');
    $('#contrat').val('');

    let iris = $('#iris').val();

    let url = $('#url').val()+iris;

    if(iris !== "") {
        params = new FormData();

        params.append( 'id', iris);

        var config = {
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        };
        axios.post(url, params, config)
            .then(function (response) {
                let data = response.data;
                $('#nom').val(data.Nom);
                $('#prenom').val(data.Prenom);
                $('#sexe').val(data.Sexe);
                $('#dateEmbauche').val(data.DateEmbauche);
                $('#projet').val(data.Projet);
                $('#contrat').val(data.Projet);
                $('#emploi').val(data.Emploi);

                let id = $('#id').val()+data.Id;
                let idReception = $('#id_reception').val()+data.Id;

                $('#demarrer').attr('href', id);
                $('#reception').attr('href', idReception);

                $('#demarrer').removeAttr('disabled');
            })
            .catch(function (error) {
                console.log(error);
            });
    }
});
