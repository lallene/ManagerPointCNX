@extends('layouts.app')

@section('link')
<style>
.table-planning thead th {
    background-color: #f8f9fa; color: #334155; font-weight: 700;
    text-align: center; font-size: 0.75rem;
}
.table-planning tbody td { font-size: 0.85rem; vertical-align: middle; text-align: center; }
.badge-in { background: #e8f5e9; color: #2e7d32; padding: 2px 6px; border-radius: 4px; }
.badge-out { background: #e3f2fd; color: #1565c0; padding: 2px 6px; border-radius: 4px; }
.badge-abs { background: #fee2e2; color: #dc2626; font-weight: 800; padding: 2px 6px; border-radius: 4px; }
.text-danger { color: #dc3545 !important; } .text-success { color: #198754 !important; } .text-warning { color: #ffc107 !important; }
.group-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; }
.site-title { background: #f1f5f9; padding: 10px 15px; font-weight: 800; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Filtres et recherche --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <form id="filter-form" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label>SITE</label>
                    <select id="site_id" class="form-select filter-trigger">
                        <option value="">Tous</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site }}">{{ $site }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>PROJET</label>
                    <select id="projet_id" class="form-select filter-trigger">
                        <option value="">Tous</option>
                        @foreach ($projetsList as $projet)
                            <option value="{{ $projet->id }}">{{ $projet->designation }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>RECHERCHE</label>
                    <input type="text" id="search-manager" class="form-control" placeholder="Nom du manager...">
                </div>
                <div class="col-md-3">
                    <label>SEMAINE</label>
                    <div class="btn-group w-100">
                        @foreach ($semaines as $sem)
                            <input type="radio" class="btn-check filter-trigger" name="week" 
                                   id="week-{{ $sem['numero'] }}" value="{{ $sem['valeur'] }}" 
                                   {{ $loop->first ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary py-2" for="week-{{ $sem['numero'] }}">S{{ $sem['numero'] }}</label>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="loader" style="display:none;" class="text-center my-3">
        <div class="spinner-border text-primary"></div>
        <span class="ms-2 fw-bold">Chargement...</span>
    </div>

    <div id="planning-container"></div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function(){
    loadPlanning();
    $('.filter-trigger').on('change', loadPlanning);
    $('#search-manager').on('keyup', function(){
        let val = $(this).val().toLowerCase();
        $("#planning-container tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

    function loadPlanning(){
        $('#loader').show();
        $.get("{{ route('getPlanningData') }}", {
            week: $('input[name="week"]:checked').val(),
            site_id: $('#site_id').val(),
            projet_id: $('#projet_id').val()
        }, function(data){
            renderTable(data);
        }).fail(function(){
            $('#planning-container').html('<div class="alert alert-danger">Erreur de chargement.</div>');
        }).always(function(){ $('#loader').hide(); });
    }

    function renderTable(data){
        const container = $('#planning-container'); container.empty();

        data.resultat.forEach(proj => {
            let header = '<tr><th>Agent</th>';
            data.dates.forEach(d => { header += `<th colspan="3">${d}</th>`; }); header += '</tr>';

            let subheader = '<tr><th></th>';
            data.dates.forEach(d => { subheader += '<th>Prévu</th><th>Réel</th><th>Écart/Retard</th>'; }); subheader += '</tr>';

            let rows = '';
            proj.superviseurs.forEach(agent => {
                let cells = `<td>${agent.nom}<br><small>${agent.fonction}</small></td>`;
                data.dates.forEach(date => {
                    let s = agent.stats[date] || {};
                    let prev = s.p_in ? `${s.p_in}-${s.p_out}` : '-';
                    let reel = s.a_in && s.a_out ? `<span class="badge-in">${s.a_in}</span>-<span class="badge-out">${s.a_out}</span>` : (s.p_in ? '<span class="badge-abs">ABS</span>' : '-');
                    let ecart = '';
                    if(s.ecart && s.ecart !== "00:00") ecart = `<span class="${s.status==='deficit'?'text-danger':'text-success'}">${s.ecart}</span>`;
                    if(s.retard && s.retard !== "00:00") ecart += `<span class="text-warning"> ⏱${s.retard}</span>`;
                    cells += `<td>${prev}</td><td>${reel}</td><td>${ecart}</td>`;
                });
                rows += `<tr>${cells}</tr>`;
            });

            container.append(`
                <div class="group-card">
                    <div class="site-title">PROJET : ${proj.projet}</div>
                    <div class="table-responsive">
                        <table class="table table-planning mb-0">
                            <thead>${header}${subheader}</thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </div>
            `);
        });
    }
});
</script>
@endpush