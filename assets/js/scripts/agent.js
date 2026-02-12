// agents.js
$(document).ready(function() {
    
    let url = $('#url').val();
    console.log(url)
    console.log("okkkkk")
    $.ajax({
        url:  url ,
        type: 'GET',
        dataType: 'json',
        success: function(agents) {
            
            var agentTable = $('#zero_config tbody');
              agents = agents.data
              
              agents.forEach(function(agents) {
                console.log(agentTable);
                agentTable.append('<tr><td>' + agents.iris + '</td><td>' + agents.nom+' '+agents.prenom + '</td><td>' + agents.sexe + '</td><td>' + agents.projet.designation + '</td><td>' + agents.emploi.designation + '</td><td>' + agents.sous_fonction.intitule  + '</td><td>' + agents.contrat.designation + '</td><td>' + agents.societe.designation + '</td><td>'  + agents.manager.nom +' '+ agents.manager.prenom + '</td></tr>');
            });
        },
        error: function(error) {
            console.log(error);
        }
    });
});
