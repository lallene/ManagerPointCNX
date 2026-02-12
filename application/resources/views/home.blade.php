@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Titre de la page --}}
    <div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2><i class="bi bi-speedometer2 me-2"></i> Tableau de bord ManagerPointCnx</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Dashboard</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Sélecteur de Semaines --}}
    <div class="d-flex flex-wrap justify-content-center mb-4">
        @foreach ($semaines as $semaine)
            <button type="button" 
                class="btn btn-sm me-2 mb-2 btn-week {{ $selectedWeek == $semaine['numero'] ? 'btn-primary' : 'btn-outline-info' }} fw-bold shadow-sm"
                data-week="{{ $semaine['numero'] }}">
                S{{ $semaine['numero'] }}<br>
                <small>{{ $semaine['debut'] }} - {{ $semaine['fin'] }}</small>
            </button>
        @endforeach
    </div>

    {{-- Filtres et Actions --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 border rounded bg-white shadow-sm h-100">
                <label class="form-label fw-bold"><i class="bi bi-calendar3"></i> Période :</label>
                <div class="d-flex gap-2">
                    <input type="date" id="filter_start" class="form-control">
                    <input type="date" id="filter_end" class="form-control">
                </div>
                <button id="btn_filter_date" class="btn btn-success w-100 mt-3">Valider la période</button>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-3 border rounded bg-white shadow-sm h-100 text-center">
                <label class="form-label fw-bold d-block mb-3">Filtrer par fonction :</label>
                <div class="d-flex justify-content-around">
                    @foreach (['Superviseur', 'Formateur', 'CQ'] as $f)
                        <div class="form-check">
                            <input class="form-check-input filter-check" type="checkbox" value="{{ $f }}" id="check_{{ $f }}">
                            <label class="form-check-label" for="check_{{ $f }}">{{ $f }}</label>
                        </div>
                    @endforeach
                </div>
                <button id="btn_filter_fonction" class="btn btn-primary w-100 mt-3">🔍 Filtrer</button>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-3 border rounded bg-white shadow-sm h-100">
                <label class="form-label fw-bold d-block mb-2">Actions d'export :</label>
                <div class="btn-group-vertical w-100 gap-2">
                    <button id="exportPdf" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    <button id="exportExcel" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                    <button id="exportJpg" class="btn btn-outline-secondary btn-sm"><i class="bi bi-image"></i> Image</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Zone du Tableau --}}
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-0">
            <div class="table-responsive" id="capture-zone">
                <table id="table-agents" class="table table-hover table-striped mb-0 w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>Workday ID</th>
                            <th>Nom & Prénom</th>
                            <th>Fonction</th>
                            <th>Email</th>
                            <th>Site</th>
                            <th>Projet</th>
                            <th>Manager</th>
                            <th>Planificateur</th>
                            <th>Date Planif.</th>
                            <th>Planning</th>
                            <th>Shift Début</th>
                            <th>Shift Fin</th>
                            <th>Pause Début</th>
                            <th>Pause Fin</th>
                            <th>H. Planifiées</th>
                            <th>H. Travail</th>
                            <th>H. Pause</th>
                            <th>H. Absentes</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Librairies d'export --}}
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Initialisation DataTables
    let table = $('#table-agents').DataTable({
        processing: true,
        serverSide: false, // On reste en client-side pour faciliter l'export complet
        ajax: {
            url: '{{ route("home.data") }}',
            data: function(d) {
                d.week = $('.btn-week.btn-primary').data('week');
                d.fonctions = $('.filter-check:checked').map(function(){ return $(this).val(); }).get();
            }
        },
        columns: [
            { data: 'workday_id' },
            { data: 'nom_prenom' },
            { data: 'fonction' },
            { data: 'email' },
            { data: 'site' },
            { data: 'projet' },
            { data: 'manager' },
            { data: 'user_planificateur' },
            { data: 'date_planification' },
            { data: 'planning' },
            { data: 'debut_shift_pointe' },
            { data: 'fin_shift_pointe' },
            { data: 'debut_pause_pointe' },
            { data: 'fin_pause_pointe' },
            { data: 'nombre_heures_planifiees' },
            { data: 'nombre_heures_travail' },
            { data: 'nombre_heures_pause' },
            { data: 'nombre_heures_absentes' }
        ],
        language: {
            "sEmptyTable": "Aucune donnée disponible",
            "sSearch": "Rechercher :",
            "sInfo": "Affichage de _START_ à _END_ sur _TOTAL_ lignes",
            "oPaginate": { "sNext": "Suivant", "sPrevious": "Précédent" }
        }
    });

    // 2. Filtres Interactifs
    $('.btn-week').click(function() {
        $('.btn-week').removeClass('btn-primary').addClass('btn-outline-info');
        $(this).addClass('btn-primary').removeClass('btn-outline-info');
        table.ajax.reload();
    });

    $('#btn_filter_fonction').click(function() {
        table.ajax.reload();
    });

    // 3. Logique d'Export Excel (Propre)
    $('#exportExcel').click(function() {
        let wb = XLSX.utils.table_to_book(document.getElementById('table-agents'), {sheet: "Planning"});
        XLSX.writeFile(wb, "Reporting_ManagerPoint.xlsx");
    });

    // 4. Logique Export PDF (Capture visuelle)
    $('#exportPdf').click(async function() {
        const { jsPDF } = window.jspdf;
        const canvas = await html2canvas(document.querySelector("#capture-zone"));
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('l', 'mm', 'a3');
        pdf.addImage(imgData, 'PNG', 10, 10, 400, 200);
        pdf.save("Planning_ManagerPoint.pdf");
    });
});
</script>
@endpush