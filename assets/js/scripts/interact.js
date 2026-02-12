function SplitTime(numberOfHours){
    var Days = Math.floor(numberOfHours/24);
    var Remainder = numberOfHours % 24;
    var Hours = Math.floor(Remainder);
    var Minutes = Math.floor(60*(Remainder-Hours));
    return({"Days":Days,"Hours":Hours,"Minutes":Minutes})
}

$('#duree_arret').keyup(function () {
    let qte = $(this).val();
    let resultats = SplitTime(qte);
    let jour = (resultats.Days > 1) ? ' Jours ' : ' Jour ';
    let heure = (resultats.Hours > 1) ? ' Heures ' : ' Heure ';
    let texte = resultats.Days + jour + ' ' + resultats.Hours + heure;
    $('#enJours').val(texte);
    $('#enJours2').val(resultats.Days);

    impactDate();
});

$('#duree_arret').change(function () {
    let qte = $(this).val();
    let resultats = SplitTime(qte);
    let jour = (resultats.Days > 1) ? ' Jours ' : ' Jour ';
    let heure = (resultats.Hours > 1) ? ' Heures ' : ' Heure ';
    let texte = resultats.Days + jour + ' ' + resultats.Hours + heure;
    $('#enJours').val(texte);
    $('#enJours2').val(resultats.Days);

    impactDate();
});

$('#assurance').change(function () {
    if($(this).val() == 'oui'){
        $('#matriculeAssuranceDiv').removeClass('d-none');
        $('#matriculeAssurance').removeAttr('readonly');
    }else{
        $('#matriculeAssurance').val('').attr('readonly', 'readonly');
        $('#matriculeAssuranceDiv').addClass('d-none');
    }
});

$('#arretMaladie').change(function () {
    if($(this).val() == 'oui'){
        $('.arretMaladieSwitch').removeClass('d-none');
        $('.repos').addClass('d-none');
        $('.analyseExterne').addClass('d-none');
    }else if ($(this).val() == 'repos'){
        $('.repos').removeClass('d-none');
        $('.arretMaladieSwitch').addClass('d-none');
        $('.analyseExterne').addClass('d-none');
    }else  if ($(this).val() == 'Analyse externe'){
        $('.analyseExterne').removeClass('d-none');
        $('.arretMaladieSwitch').addClass('d-none');
        $('.repos').addClass('d-none');
    }else{
        $('.arretMaladieSwitch').addClass('d-none');
        $('.repos').addClass('d-none');
        $('.analyseExterne').addClass('d-none');
    }
});



$('#motif_consultation_id').change(function () {
    if($(this).val() == 25){
        $('.motif_consultation_autre').removeClass('d-none');

    }else{
        $('.motif_consultation_autre').addClass('d-none');
    }
});






$('#justificatifValide').change(function () {
    if($(this).val() == 'oui'){
        $('.arretMaladieSwitch').removeClass('d-none');
        $('.repos').addClass('d-none');
    }else if ($(this).val() == 'non') {
        $('.arretMaladieSwitch').addClass('d-none');
        $('.repos').removeClass('d-none');
    }else {
        $('.arretMaladieSwitch').removeClass('d-none');
        $('.repos').addClass('d-none');
    }
});




$('#repriseService').change(function () {
    if($(this).val() == 'inapte'){
        $('.repriseServiceswitch').removeClass('d-none');

    }else {
        $('.repriseServiceswitch').addClass('d-none');

    }
});






$('#debutArret').change(function () {
    impactDate();
});

function impactDate(){
    let startDate = moment($('#debutArret').val());

    if(startDate.isValid()){
        let dayToAdd = $('#enJours2').val();
        let new_date = moment(startDate, "YYYY-MM-DD").add(dayToAdd, 'days');
        let endDate = moment(new_date).format('YYYY-MM-DD');

        $('#dateReprise').val(endDate);
    }
}

