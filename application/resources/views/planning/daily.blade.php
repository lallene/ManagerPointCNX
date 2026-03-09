@extends('layouts.app')

@section('link')
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light.css" />

    <style>
        :root {
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --accent-green: #2ECC71;
            --accent-green-dark: #27ae60;
            --theo-gray: rgba(226, 232, 240, 0.6);
            --pause-gold: #F1C40F;
            --danger-red: #E74C3C;
            --hour-h: 60px;
            --sidebar-w: 65px;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body { background: var(--bg-light); color: var(--text-dark); font-family: 'Inter', sans-serif; }

        /* KPI Cards */
        .kpi-wrapper { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            flex: 1;
            min-width: 150px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .kpi-card h6 { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; font-weight: 700; }
        .kpi-card h3 { margin: 0; font-weight: 800; }

        /* Timeline Layout */
        .graph-frame {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .time-sidebar {
            width: var(--sidebar-w);
            background: #f1f5f9;
            border-right: 2px solid #cbd5e1;
            position: sticky;
            left: 0;
            z-index: 101;
        }

        .hour-label {
            height: var(--hour-h);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            border-bottom: 1px dotted #cbd5e1;
        }

        .manager-col {
            min-width: 220px;
            border-right: 1px solid #f1f5f9;
            position: relative;
            background: white;
        }

        .manager-head {
            height: 110px;
            padding: 10px;
            background: #ffffff;
            border-bottom: 3px solid var(--accent-green);
            position: sticky;
            top: 0;
            z-index: 90;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Blocks Styles */
        .theoretical-slot {
            position: absolute;
            width: 90%;
            left: 5%;
            background: var(--theo-gray);
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            z-index: 5;
            cursor: help;
        }

        .block-work {
            position: absolute;
            width: 80%;
            left: 10%;
            background: linear-gradient(180deg, var(--accent-green) 0%, var(--accent-green-dark) 100%);
            color: white;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: help;
            transition: transform 0.1s;
        }
        .block-work:hover { transform: scale(1.02); z-index: 100; }

        .block-pause {
            position: absolute;
            width: 70%;
            left: 15%;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 15;
            cursor: help;
        }

        .block-anomaly {
            position: absolute;
            width: 60%;
            left: 20%;
            text-align: center;
            font-size: 9px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            z-index: 20;
            animation: pulse 2s infinite;
            border-radius: 4px;
        }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }

        .now-line-custom {
            position: absolute;
            left: 0; right: 0;
            border-top: 2px solid #ef4444;
            z-index: 102;
            pointer-events: none;
        }
        .now-line-custom::before {
            content: 'DIRECT';
            position: absolute;
            left: 0; top: -10px;
            background: #ef4444; color: white;
            font-size: 9px; padding: 1px 4px; border-radius: 0 4px 4px 0;
            font-weight: bold;
        }

        /* Tooltip Custom Style */
        .tippy-box[data-theme~='light'] {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid p-4" style="margin-top: 60px;">
        <div id="kpi-container" class="kpi-wrapper"></div>

        <div class="card border-0 shadow-sm p-3 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">CHERCHER</label>
                    <input type="text" id="manager_search" class="form-control form-control-sm" placeholder="Nom du manager...">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">DATE</label>
                    <input type="date" id="target_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" onchange="loadData()">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">SITE</label>
                    <select id="site_f" class="form-select form-select-sm" onchange="updateProjetFilter()">
                        <option value="">Tous les sites</option>
                        @foreach ($sites as $s) <option value="{{ $s }}">{{ $s }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted">PROJET</label>
                    <select id="projet_f" class="form-select form-select-sm" onchange="loadData()">
                        <option value="">Tous les projets</option>
                        @foreach ($projetsList as $p) <option value="{{ $p->id }}" data-site="{{ $p->site_id }}">{{ $p->designation }}</option> @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div id="graph-zone" class="graph-frame">
            <div class="p-5 text-center text-muted">
                <div class="spinner-border spinner-border-sm me-2"></div> Chargement de la timeline...
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const PX_H = 60, START_H = 6, END_H = 22;
        let allData = [];

        function tToF(t) {
            if (!t || t === '--:--') return null;
            let p = t.split(':');
            return parseInt(p[0]) + (parseInt(p[1] || 0) / 60);
        }

        function loadData() {
            const params = {
                date: $('#target_date').val(),
                site_id: $('#site_f').val() || null,
                projet_id: $('#projet_f').val() || null
            };

            $.get("{{ route('getDailyPlanningData') }}", params, function(data) {
                allData = Array.isArray(data) ? data : (data.data || []);
                renderMain(allData);
            }).fail(() => {
                $('#graph-zone').html('<div class="alert alert-danger m-3">Erreur lors de la récupération des données.</div>');
            });
        }

        function renderMain(data) {
            if (!data.length) {
                $('#graph-zone').html('<div class="p-5 text-center text-muted">Aucune donnée pour cette sélection</div>');
                return;
            }

            let flatManagers = [];
            data.forEach(p => p.top_managers.forEach(tm => tm.managers.forEach(m => flatManagers.push(m))));
            updateKPIs(flatManagers);

            let html = '';
            data.forEach(p => {
                html += `
                <div class="projet-group mb-4">
                    <div class="p-2 bg-dark text-white fw-bold small d-flex justify-content-between">
                        <span><i class="fas fa-project-diagram me-2 text-primary"></i>${p.projet}</span>
                        <span class="badge bg-primary">${p.site}</span>
                    </div>`;

                p.top_managers.forEach(tm => {
                    html += `
                    <div class="superviseur-section border-bottom">
                        <div class="p-2 small bg-light fw-bold border-bottom">
                            <i class="fas fa-user-tie me-2 text-secondary"></i>SUPERVISEUR : ${tm.top_manager}
                        </div>
                        <div class="d-flex overflow-auto">
                            <div class="time-sidebar">${renderTimeline()}</div>
                            <div class="d-flex position-relative flex-grow-1" style="min-height:${(END_H - START_H + 1) * PX_H}px; background-image: linear-gradient(#f1f5f9 1px, transparent 1px); background-size: 100% ${PX_H}px;">
                                <div class="now-line-custom"></div>
                                ${tm.managers.map(renderManager).join('')}
                            </div>
                        </div>
                    </div>`;
                });
                html += `</div>`;
            });

            $('#graph-zone').html(html);
            updateNowIndicator();

            // Initialisation des infobulles Tippy
            tippy('[data-tippy-content]', {
                allowHTML: true,
                theme: 'light',
                placement: 'top',
                arrow: true,
                animation: 'fade',
            });
        }

        function renderManager(m) {
            let realContent = '', pauses = '', anomalies = '', theoContent = '';
            const nowF = new Date().getHours() + (new Date().getMinutes() / 60);

            // 1. PLANNING THÉORIQUE
            let st = m.debut_theorique || "08:00", et = m.fin_theorique || "17:00";
            let stF = tToF(st), etF = tToF(et);
            if (stF) {
                theoContent = `<div class="theoretical-slot" 
                    data-tippy-content="<b>Planning Prévu</b><br>${st} - ${et}"
                    style="top:${(stF - START_H) * PX_H}px; height:${(etF - stF) * PX_H}px;"></div>`;
            }

            // 2. SEGMENTS RÉELS (Production)
            if (m.segments) {
                m.segments.forEach(s => {
                    let sF = tToF(s.start), eF = (s.end && s.end !== '--:--') ? tToF(s.end) : nowF;
                    let isLive = !(s.end && s.end !== '--:--');
                    if (sF) {
                        let top = (sF - START_H) * PX_H;
                        let h = Math.max((eF - sF) * PX_H, 12);
                        
                        let tooltip = `
                            <div class='text-start'>
                                <b class='text-success'>PRODUCTION</b><br>
                                <b>Début :</b> ${s.start}<br>
                                <b>Fin :</b> ${isLive ? "<span class='badge bg-danger'>EN COURS</span>" : s.end}<br>
                                <hr class='my-1'>
                                <b>Durée :</b> ${(eF - sF).toFixed(2)}h
                            </div>`;

                        realContent += `
                        <div class="block-work" data-tippy-content="${tooltip}" style="top:${top}px; height:${h}px;">
                            <span>${(eF - sF).toFixed(1)}h</span>
                        </div>`;
                    }
                });
            }

            // 3. PAUSES
            if (m.pauses) {
                m.pauses.forEach(p => {
                    let psF = tToF(p.start), peF = tToF(p.end) || nowF;
                    if (psF) {
                        let tooltipPause = `<b>Pause :</b> ${p.start} - ${p.end || 'En cours'}<br><b>Durée :</b> ${p.minutes} min`;
                        pauses += `<div class="block-pause" 
                            data-tippy-content="${tooltipPause}"
                            style="top:${(psF - START_H) * PX_H}px; height:${Math.max((peF - psF) * PX_H, 15)}px; background:${p.minutes > 60 ? 'var(--danger-red)' : 'var(--pause-gold)'};">
                            ${p.minutes}'
                        </div>`;
                    }
                });
            }

            // 4. RETARD
            if (m.start_status === 'RETARD') {
                anomalies += `<div class="block-anomaly bg-danger" style="top:${(tToF(m.real_start) - START_H) * PX_H - 22}px; height:20px;">RETARD</div>`;
            }

            return `
            <div class="manager-col">
                <div class="manager-head text-center">
                    <div class="fw-bold text-truncate" style="font-size:0.8rem;">${m.nom}</div>
                    <div class="text-muted small" style="font-size:0.65rem;">${m.role || 'MANAGER'}</div>
                    <div class="badge ${m.start_status === 'RETARD' ? 'bg-danger' : 'bg-success'} mt-1" style="font-size:0.6rem;">
                        LOG: ${m.real_start || '--:--'}
                    </div>
                </div>
                <div class="position-relative h-100">
                    ${theoContent}
                    ${realContent}
                    ${pauses}
                    ${anomalies}
                </div>
            </div>`;
        }

        function renderTimeline() {
            let h = '';
            for (let i = START_H; i <= END_H; i++) h += `<div class="hour-label">${i}:00</div>`;
            return h;
        }

        function updateNowIndicator() {
            const n = new Date();
            const h = n.getHours() + (n.getMinutes() / 60);
            if (h >= START_H && h <= END_H) {
                $('.now-line-custom').css({ top: (h - START_H) * PX_H + 'px', display: 'block' });
            } else $('.now-line-custom').hide();
        }

        function updateKPIs(managers) {
            let k = { total: managers.length, retards: 0, hours: 0 };
            managers.forEach(m => {
                if (m.start_status === 'RETARD') k.retards++;
                if (m.segments) m.segments.forEach(s => {
                    let sF = tToF(s.start), eF = (s.end && s.end !== '--:--') ? tToF(s.end) : (new Date().getHours() + new Date().getMinutes()/60);
                    k.hours += Math.max(0, eF - sF);
                });
            });

            $('#kpi-container').html(`
                <div class="kpi-card"><h6>Managers</h6><h3>${k.total}</h3></div>
                <div class="kpi-card"><h6>Retards</h6><h3 class="text-danger">${k.retards}</h3></div>
                <div class="kpi-card"><h6>Heures Prod.</h6><h3 class="text-success">${k.hours.toFixed(1)}h</h3></div>
            `);
        }

        $(document).ready(() => {
            loadData();
            setInterval(() => {
                updateNowIndicator();
                // Optionnel: rafraîchir les données toutes les 5 mins
            }, 60000);

            $('#manager_search').on('input', function() {
                const val = $(this).val().toLowerCase();
                $('.manager-col').each(function() {
                    $(this).toggle($(this).find('.fw-bold').text().toLowerCase().includes(val));
                });
            });
        });

        function updateProjetFilter() {
            const site = $('#site_f').val();
            $('#projet_f option').each(function() {
                $(this).toggle(!site || $(this).data('site') == site || !$(this).val());
            });
            loadData();
        }
    </script>
@endpush