@extends('layouts.app')

@section('link')
<style>
    /* Design global */
    .table-planning thead th { background-color: #f8fafc; color: #475569; font-size: 0.75rem; text-transform: uppercase; padding: 12px !important; border-bottom: 2px solid #e2e8f0; }
    .table-planning tbody td { vertical-align: middle; text-align: center; padding: 12px 8px !important; border: 1px solid #edf2f7; background: #fff; }
    .group-card { background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 2.5rem; shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
    
    /* Affichage des horaires */
    .time-in { color: #2563eb; font-weight: 800; font-size: 0.9rem; display: block; }
    .time-out { color: #64748b; font-weight: 600; font-size: 0.8rem; display: block; margin-top: 2px; }
    .day-off { color: #cbd5e1; font-size: 0.75rem; font-style: italic; font-weight: 500; }
    
    /* Visibilité des boutons d'action */
    .action-buttons { display: flex; gap: 8px; align-items: center; }
    .btn-export { width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s;   background: #003d5b5c; }
    .btn-excel { border: 1px solid #198754; color: #198754; }
    .btn-excel:hover { background: #198754; color: #fff; }
    .btn-pdf { border: 1px solid #dc3545; color: #dc3545; }
    .btn-pdf:hover { background: #dc3545; color: #fff; }
    .btn-img { border: 1px solid #0d6efd; color: #0d6efd; }
    .btn-img:hover { background: #0d6efd; color: #fff; }

    @media print { .no-export { display: none !important; } }
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="filter-form" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">SITE</label>
                    <select id="site_id" class="form-select filter-trigger">
                        <option value="">Tous les sites</option>
                        @foreach ($sites as $site) <option value="{{ $site }}">{{ $site }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">PROJET</label>
                    <select id="projet_id" class="form-select filter-trigger">
                        <option value="">Tous les projets</option>
                        @foreach ($projetsList as $p) <option value="{{ $p->id }}">{{ $p->designation }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted">SEMAINE</label>
                    <div class="btn-group w-100 shadow-sm">
                        @foreach ($semaines as $sem)
                            <input type="radio" class="btn-check filter-trigger" name="week" id="w-{{ $sem['numero'] }}" value="{{ $sem['valeur'] }}" {{ $loop->iteration == 4 ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary py-2" for="w-{{ $sem['numero'] }}">S{{ $sem['numero'] }}</label>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="loader" class="text-center py-5" style="display:none;"><div class="spinner-border text-primary"></div></div>
    <div id="planning-container"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
$(document).ready(function() {
    loadPlanning();
    $('.filter-trigger').on('change', loadPlanning);

    function loadPlanning() {
        $('#loader').show();
        $('#planning-container').empty();
        $.get("{{ route('getPlanningData') }}", {
            week: $('input[name="week"]:checked').val(),
            site_id: $('#site_id').val(),
            projet_id: $('#projet_id').val()
        }, function(data) {
            renderTable(data);
        }).always(() => $('#loader').hide());
    }

    function renderTable(data) {
        const container = $('#planning-container');
        if (!data.resultat.length) {
            container.html('<div class="alert alert-info text-center shadow-sm">Aucun planning trouvé.</div>');
            return;
        }

        data.resultat.forEach((proj, index) => {
            const cardId = `card-proj-${index}`;
            let header = `<tr><th class="text-start ps-3" style="min-width:200px">Manager</th>`;
            data.dates.forEach(d => {
                header += `<th class="text-center border-start">${new Date(d).toLocaleDateString('fr-FR', { weekday: 'short', day: '2-digit' })}</th>`;
            });
            header += `</tr>`;

            let rows = '';
            proj.superviseurs.forEach(agent => {
                let cells = `<td class="text-start ps-3 fw-bold">${agent.nom}</td>`;
                data.dates.forEach(date => {
                    let s = agent.stats[date] || {};
                    cells += `<td class="text-center border-start">${s.p_in ? `<span class="time-in">${s.p_in}</span><span class="time-out">${s.p_out}</span>` : '<span class="day-off">OFF</span>'}</td>`;
                });
                rows += `<tr>${cells}</tr>`;
            });

            container.append(`
                <div class="group-card shadow-sm" id="${cardId}">
                    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-primary text-uppercase"><i class="fas fa-calendar-alt me-2"></i>${proj.projet}</span>
                        <div class="action-buttons no-export">
                            <span class="badge bg-secondary me-2">${proj.superviseurs.length} Managers</span>
                            <button class="btn-export btn-excel" onclick="exportToExcel('${cardId}', '${proj.projet}')" title="Excel"><i class="fas fa-file-excel"></i></button>
                            <button class="btn-export btn-pdf" onclick="exportToPDF('${cardId}', '${proj.projet}')" title="PDF"><i class="fas fa-file-pdf"></i></button>
                            <button class="btn-export btn-img" onclick="exportToImage('${cardId}', '${proj.projet}')" title="Image"><i class="fas fa-image"></i></button>
                        </div>
                    </div>
                    <div class="table-responsive"><table class="table table-planning mb-0"><thead>${header}</thead><tbody>${rows}</tbody></table></div>
                </div>
            `);
        });
    }
});

function exportToExcel(cardId, projetName) {
    const card = document.getElementById(cardId);
    const table = card.querySelector('table');
    const rows = [];
    
    // Extraction propre pour Excel (sans le HTML)
    Array.from(table.rows).forEach(row => {
        const rowData = Array.from(row.cells).map(cell => {
            // Si c'est un horaire, on remplace le retour à la ligne par un espace
            return cell.innerText.replace(/\n/g, ' - ').trim();
        });
        rows.push(rowData);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    
    // Style Excel : Ajustement de la largeur des colonnes
    const wscols = [{wch: 30}, {wch: 15}, {wch: 15}, {wch: 15}, {wch: 15}, {wch: 15}, {wch: 15}, {wch: 15}];
    ws['!cols'] = wscols;

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Planning");
    XLSX.writeFile(wb, `Planning_${projetName.replace(/\s+/g, '_')}.xlsx`);
}

function exportToImage(cardId, projet) {
    const el = document.getElementById(cardId);
    const btns = el.querySelector('.no-export');
    btns.style.display = 'none';
    html2canvas(el, { scale: 2 }).then(canvas => {
        const a = document.createElement('a');
        a.href = canvas.toDataURL();
        a.download = `Planning_${projet}.png`;
        a.click();
        btns.style.display = 'flex';
    });
}

function exportToPDF(cardId, projet) {
    const { jsPDF } = window.jspdf;
    const el = document.getElementById(cardId);
    const btns = el.querySelector('.no-export');
    btns.style.display = 'none';
    html2canvas(el, { scale: 2 }).then(canvas => {
        const pdf = new jsPDF('l', 'mm', 'a4');
        const imgWidth = pdf.internal.pageSize.getWidth() - 20;
        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 10, 10, imgWidth, (canvas.height * imgWidth) / canvas.width);
        pdf.save(`Planning_${projet}.pdf`);
        btns.style.display = 'flex';
    });
}
</script>
@endpush