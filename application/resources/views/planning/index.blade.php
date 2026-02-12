@extends('layouts.app')

@section('content')
<meta charset="UTF-8">
<meta http-equiv="Content-Language" content="fr">

<style>
    @media (min-width: 1400px) {
        .container, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl {
            max-width: 1492px !important;
        }
    }

    /* Style global tableau */
    .table {
        border-collapse: separate !important;
        border-spacing: 0 8px; /* espace vertical entre lignes */
    }

    /* En-tête tableau */
    .table thead th {
        background-color: #cfe2ff; /* bleu clair bootstrap */
        color: #084298; /* bleu foncé */
        font-weight: 700;
        vertical-align: middle;
        text-align: center;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        padding: 12px 10px;
        user-select: none;
    }

    /* Colonnes Agent */
    tbody td:first-child {
        font-weight: 600;
        color: #0d6efd;
        min-width: 160px;
        vertical-align: middle;
    }

    /* Cellules planning */
    tbody td {
        background-color: #f8f9fa;
        vertical-align: middle;
        min-width: 140px;
        padding: 8px 6px;
        transition: background-color 0.3s ease;
        border-radius: 6px;
    }

    /* Hover sur lignes */
    tbody tr:hover td {
        background-color: #e7f1ff;
        cursor: default;
    }

    /* Badge planning */
    .badge.bg-success {
        background-color: #198754 !important;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 6px 12px;
        border-radius: 12px;
        display: inline-block;
        box-shadow: 0 2px 6px rgb(25 135 84 / 0.4);
        user-select: none;
    }

    /* Texte cellule vide */
    .text-muted {
        font-style: italic;
        font-size: 1.1rem;
        color: #adb5bd !important;
        user-select: none;
    }

    /* Boutons sélection semaine */
    .btn-sm {
        min-width: 75px;
        padding: 8px 10px;
        font-weight: 700;
        font-size: 0.9rem;
        user-select: none;
        border-radius: 6px;
        transition: background-color 0.25s ease;
    }
    .btn-sm:hover {
        filter: brightness(0.9);
    }

    /* Titre principal */
    .styled-title {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 2.5rem;
        color: #0d6efd;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.15);
        letter-spacing: 1px;
        user-select: none;
        margin-top: 60px;
        margin-bottom: 0px;
        text-align: center;
    }
     .styled-title {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 2.5rem;
        color: #0d6efd;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.15);
        letter-spacing: 1px;
        user-select: none;
        margin-top: 70px;
        margin-bottom: 40px;
        text-align: center;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2>🗓️ Planification Hebdomadaire</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Planification Hebdomadaire</span>
                </div>
            </div>
        </div>
    </div>
    {{-- Grille de semaines --}}
    <div class="d-flex flex-wrap justify-content-center mb-4">
        @foreach ($semaines as $semaine)
            <form method="GET" action="{{ route('planification') }}" class="me-2 mb-2">
                <input type="hidden" name="week" value="{{ $semaine['numero'] }}">
                <button type="submit" class="btn btn-sm {{ $selectedWeek == $semaine['numero'] ? 'btn-primary' : 'btn-outline-info' }} fw-bold shadow-sm">
                    S{{ $semaine['numero'] }}<br>
                    <small>{{ $semaine['debut'] }} - {{ $semaine['fin'] }}</small>
                </button>
            </form>
        @endforeach
    </div>
    <div class="row g-3 align-items-stretch mb-4">

    <!-- Formulaire d'import -->
    <div class="col-md-4 d-flex">
        <form action="{{ route('plannings.import') }}" method="POST" enctype="multipart/form-data"
              class="p-3 border rounded bg-light w-100 d-flex flex-column justify-content-between">
            @csrf

            <div>
                <label for="file" class="form-label" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 1.25rem; font-weight: 600;">  📄 Importer un fichier Excel :</label>
                <input type="file" name="file" id="file" class="form-control mb-2" required>
                <input type="hidden" name="week" value="{{ $week ?? \Carbon\Carbon::now()->isoWeek() }}">
            </div>

            <button type="submit" class="btn btn-success mt-auto">
                📥 Importer le planning
            </button>
        </form>
    </div>

    <!-- Formulaire de filtres -->
    <div class="col-md-4 d-flex">
        <form method="GET" action="{{ route('planification') }}"
              class="p-3 border rounded bg-light w-100 d-flex flex-column justify-content-between">
                    <div class="mb-3 d-flex justify-content-center align-items-center" style="gap: 2rem; font-size: 1.25rem;">
                        <label class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" name="fonctions[]" value="Superviseur"
                                {{ in_array('Superviseur', request('fonctions', [])) ? 'checked' : '' }}>
                            <span class="form-check-label">Superviseur</span>
                        </label>

                        <label class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" name="fonctions[]" value="Formateur"
                                {{ in_array('Formateur', request('fonctions', [])) ? 'checked' : '' }}>
                            <span class="form-check-label">Formateur</span>
                        </label>

                        <label class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" name="fonctions[]" value="CQ"
                                {{ in_array('CQ', request('fonctions', [])) ? 'checked' : '' }}>
                            <span class="form-check-label">CQ</span>
                        </label>
                    </div>



            <button type="submit" class="btn btn-primary mt-auto">
                🔍 Filtrer
            </button>
        </form>
    </div>

    <!-- Boutons d'export -->
    <div class="col-md-4 d-flex">
        <div class="p-3 border rounded bg-light w-100 d-flex flex-column justify-content-between">
            <div>
                <button id="exportPdf" class="btn btn-outline-danger w-100 mb-2">📄 Exporter PDF</button>
                <button id="exportJpg" class="btn btn-outline-secondary w-100 mb-2">🖼️ Exporter JPG</button>
                <button id="exportExcel" class="btn btn-outline-success w-100">📊 Exporter Excel</button>
            </div>
        </div>
    </div>

</div>






    {{-- Formulaire de planning --}}
    <form method="POST" action="{{ route('planning.store') }}">
        @csrf
        <input type="hidden" name="week" value="{{ $selectedWeek }}">

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle table-hover">
                <thead class="table-info text-center">
                    <tr>
                        <th>Agent</th>
                        @foreach (['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'] as $i => $jour)
                            @php
                                $date = \Carbon\Carbon::now()->startOfYear()->addWeeks($selectedWeek - 1)->startOfWeek()->addDays($i);
                            @endphp
                            <th>{{ $jour }}<br><small>{{ $date->format('d/m') }}</small></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                <tbody>
    @foreach ($agents as $agent)
        <tr>
            <td>{{ $agent->nom }} {{ $agent->prenom }} {{ $agent->workday_id }}</td>

            @foreach (range(0, 6) as $i)
                @php
                    $jourDate = \Carbon\Carbon::now()->startOfYear()->addWeeks($selectedWeek - 1)->startOfWeek()->addDays($i)->format('Y-m-d');
                    $planningKey = $agent->id . '-' . $jourDate;
                    $planning = $plannings[$planningKey] ?? null;
                    $datePasse = \Carbon\Carbon::parse($jourDate)->isBefore(\Carbon\Carbon::today());
                @endphp

                <td style="min-width: 140px;">
                    <input type="hidden" name="plannings[{{ $agent->id }}][{{ $jourDate }}][jour]" value="{{ $jourDate }}">
                    <input type="hidden" name="plannings[{{ $agent->id }}][{{ $jourDate }}][agent_id]" value="{{ $agent->id }}">

                    <div class="d-flex gap-1">
                        <input type="time"
                               class="form-control form-control-sm heure-debut {{ $datePasse ? 'bg-light text-muted' : '' }}"
                               name="plannings[{{ $agent->id }}][{{ $jourDate }}][heure_debut]"
                               value="{{ $planning->heure_debut ?? '' }}"
                               style="width: 80px;"
                               placeholder="Début"
                               {{ $datePasse ? 'disabled' : '' }}>

                        <input type="time"
                               class="form-control form-control-sm heure-fin {{ $datePasse ? 'bg-light text-muted' : '' }}"
                               name="plannings[{{ $agent->id }}][{{ $jourDate }}][heure_fin]"
                               value="{{ $planning->heure_fin ?? '' }}"
                               style="width: 80px;"
                               placeholder="Fin"
                               {{ $datePasse ? 'disabled' : '' }}>
                    </div>
                </td>
            @endforeach
        </tr>
    @endforeach
</tbody>

                </tbody>
            </table>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-success shadow-sm">💾 Enregistrer</button>
        </div>
    </form>
</div>

{{-- Auto-calcul heure de fin --}}
<!-- html2canvas (capture d’élément en image) -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<!-- jsPDF (génération de PDF) -->
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<!-- SheetJS (génération Excel) -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const heureDebuts = document.querySelectorAll('.heure-debut');

        heureDebuts.forEach(input => {
            input.addEventListener('change', function () {
                const debut = this.value;
                if (!debut) return;

                const [hours, minutes] = debut.split(':').map(Number);
                let finHours = (hours + 8) % 24;
                const fin = `${finHours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;

                const container = this.closest('.d-flex');
                const inputFin = container.querySelector('.heure-fin');
                if (inputFin) {
                    inputFin.value = fin;
                }
            });
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tableContainer = document.querySelector('.table-responsive');

  // PDF
  document.getElementById('exportPdf').addEventListener('click', async () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');
    const canvas = await html2canvas(tableContainer, { scale: 2 });
    const imgData = canvas.toDataURL('image/png');
    const imgProps = doc.getImageProperties(imgData);
    const pdfWidth = doc.internal.pageSize.getWidth();
    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
    doc.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
    doc.save('planning.pdf');
  });

  // JPG
  document.getElementById('exportJpg').addEventListener('click', async () => {
    const canvas = await html2canvas(tableContainer, { scale: 2 });
    const imgData = canvas.toDataURL('image/jpeg', 0.9);
    const link = document.createElement('a');
    link.href = imgData;
    link.download = 'planning.jpg';
    link.click();
  });

  // Excel
  document.getElementById('exportExcel').addEventListener('click', () => {
  // Récupérer la table HTML
  const table = document.querySelector('#agentTable')
             || document.querySelector('.table-responsive table');

  // 1) Générer un workbook à partir de la table
  const wb = XLSX.utils.table_to_book(table, { sheet: 'Planning' });

  // 2) Récupérer la worksheet
  const ws = wb.Sheets['Planning'];

  // 3) Parcourir toutes les adresses de cellules
  Object.keys(ws).forEach(addr => {
    // ignorer les métadonnées
    if (addr[0] === '!') return;

    const cell = ws[addr];
    // si la valeur est une chaîne contenant uniquement des chiffres
    if (typeof cell.v === 'string' && cell.v.match(/^[-+]?\d+(\.\d+)?$/)) {
      // convertir en nombre
      const num = parseFloat(cell.v);
      cell.v = num;
      cell.t = 'n'; // indique à SheetJS que c'est un nombre
    }
  });

  // 4) Écrire et télécharger
  XLSX.writeFile(wb, 'planning.xlsx');
});

});
</script>

@endsection
