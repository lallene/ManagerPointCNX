@extends('layouts.app')

@section('link')
    <style>
        :root {
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --accent-green: #2ECC71;
            --pause-gold: #F1C40F;
            --danger-red: #E74C3C;
            --hour-h: 60px;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            background: var(--bg-light);
            color: var(--text-dark);
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        /* KPI Style */
        .kpi-wrapper {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .kpi-card {
            background: var(--card-bg);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            min-width: 160px;
            flex: 1;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .kpi-card h6 {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .kpi-card h3 {
            margin: 0;
            font-weight: 800;
            color: #0f172a;
        }

        /* Graph Layout */
        .graph-frame {
            background: var(--card-bg);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .time-sidebar {
            width: 65px;
            background: #f1f5f9;
            border-right: 1px solid #e2e8f0;
            position: sticky;
            left: 0;
            z-index: 100;
        }

        .hour-label {
            height: var(--hour-h);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: #94a3b8;
            border-bottom: 1px dotted #cbd5e1;
        }

        .manager-col {
            min-width: 280px;
            border-right: 1px solid #f1f5f9;
            position: relative;
        }

        .manager-head {
            height: 110px;
            padding: 15px;
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
        .block-work,
        .block-pause,
        .block-anomaly {
            position: absolute;
            width: 88%;
            left: 6%;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .block-work {
            background: rgba(46, 204, 113, 0.9);
            color: #fff;
            flex-direction: column;
            justify-content: space-between;
            padding: 4px 0;
            border: 1px solid #27ae60;
        }

        .block-pause {
            background: var(--pause-gold);
            color: #453505;
            border: 1px solid #d4ac0d;
        }

        .block-anomaly {
            background: var(--danger-red);
            color: #fff;
            text-transform: uppercase;
            border: 1px solid #c0392b;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }

            100% {
                opacity: 1;
            }
        }

        /* Sticky Headers */
        .projet-container>.bg-dark {
            position: sticky;
            top: 0;
            z-index: 110;
        }

        .superviseur-section .bg-light {
            position: sticky;
            top: 38px;
            z-index: 105;
        }

        .now-line-custom {
            position: absolute;
            left: 0;
            right: 0;
            border-top: 2px solid #ef4444;
            z-index: 200;
            pointer-events: none;
        }

        .now-line-custom::before {
            content: 'NOW';
            position: absolute;
            left: 0;
            top: -10px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 0 4px 4px 0;
        }

        .tooltip-box {
            position: absolute;
            background: #0f172a;
            color: #fff;
            font-size: 0.75rem;
            padding: 8px 12px;
            border-radius: 6px;
            z-index: 9999;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
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
                    <input type="text" id="manager_search" class="form-control form-control-sm shadow-none"
                        placeholder="Nom du manager...">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">DATE CIBLE</label>
                    <input type="date" id="target_date" class="form-control form-control-sm shadow-none"
                        value="{{ date('Y-m-d') }}" onchange="loadData()">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">SITE</label>
                    <select id="site_f" class="form-select form-select-sm shadow-none" onchange="updateProjetFilter()">
                        <option value="">Tous les sites</option>
                        @foreach ($sites as $s)
                            <option value="{{ $s }}" {{ $selectedSiteId == $s ? 'selected' : '' }}>
                                {{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted">PROJET</label>
                    <select id="projet_f" class="form-select form-select-sm shadow-none" onchange="loadData()">
                        <option value="">Tous les projets rattachés</option>
                        @foreach ($projetsList as $p)
                            <option value="{{ $p->id }}" data-site="{{ $p->site_id }}"
                                {{ $selectedProjetId == $p->id ? 'selected' : '' }}>{{ $p->designation }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div id="graph-zone" class="graph-frame">
            <div class="p-5 text-center text-muted">
                <div class="spinner-border spinner-border-sm me-2"></div>
                Initialisation du Command Center...
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const PX_H = 60,
            START_H = 6;
        let allData = [];

        function tToF(t) {
            if (!t) return 0;
            let p = t.split(':');
            return parseInt(p[0]) + (parseInt(p[1] || 0) / 60);
        }

        function calculateKPI(flatManagers) {
            let kpi = {
                total: 0,
                retards: 0,
                departAnticipe: 0,
                depassement: 0,
                workHours: 0
            };
            flatManagers.forEach(m => {
                kpi.total++;
                if (m.start_status === 'RETARD') kpi.retards++;
                if (m.end_status === 'DEPART_ANTICIPE') kpi.departAnticipe++;
                if (m.pauses) m.pauses.forEach(p => {
                    if (p.status === 'DEPASSEMENT') kpi.depassement++;
                });
                if (m.segments) m.segments.forEach(s => {
                    kpi.workHours += tToF(s.end) - tToF(s.start);
                });
            });
            return kpi;
        }

        function renderKPI(k) {
            $('#kpi-container').html(`
                <div class="kpi-card"><h6>Managers</h6><h3>${k.total}</h3></div>
                <div class="kpi-card"><h6>Retards</h6><h3 class="${k.retards > 0 ? 'text-danger' : ''}">${k.retards}</h3></div>
                <div class="kpi-card"><h6>Départs Anticipe</h6><h3>${k.departAnticipe}</h3></div>
                <div class="kpi-card"><h6>Over-Pause</h6><h3 class="text-warning">${k.depassement}</h3></div>
                <div class="kpi-card"><h6>Capacité Prod</h6><h3>${k.workHours.toFixed(1)}h</h3></div>
            `);
        }

        function loadData() {
            const params = {
                date: $('#target_date').val(),
                site_id: $('#site_f').val() || null,
                projet_id: $('#projet_f').val() || null
            };

            $('#graph-zone').html(
                `<div class="p-5 text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Chargement...</div>`
            );

            $.get("{{ route('getDailyPlanningData') }}", params, data => {
                allData = data;
                renderMain(data);
            });
        }

        function renderMain(data) {
            let allManagersFlat = [];
            data.forEach(p => p.top_managers.forEach(tm => tm.managers.forEach(m => allManagersFlat.push(m))));
            renderKPI(calculateKPI(allManagersFlat));

            let html = '';
            data.forEach(p => {
                html += `
                <div class="projet-container mb-5 shadow-sm">
                    <div class="p-2 bg-dark text-white fw-bold small d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-project-diagram me-2 text-primary"></i>PROJET : ${p.projet}</span>
                        <span class="badge bg-secondary">SITE : ${p.site}</span>
                    </div>`;
                p.top_managers.forEach(tm => {
                    html += `
                    <div class="superviseur-section bg-white border-bottom">
                        <div class="p-2 small fw-bold text-muted bg-light border-bottom">SUPERVISEUR : ${tm.top_manager}</div>
                        <div class="d-flex overflow-auto">
                            <div class="time-sidebar">${renderTimeline()}</div>
                            <div class="d-flex position-relative flex-grow-1" style="min-height:${17 * PX_H}px;">
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
        }

        function renderManager(m) {
            let segs = '',
                pauses = '',
                anomalies = '',
                tW = 0,
                tP = 0;

            m.segments.forEach(s => {
                let sF = tToF(s.start),
                    eF = tToF(s.end),
                    top = (sF - START_H) * PX_H,
                    h = (eF - sF) * PX_H;
                tW += (eF - sF);
                segs += `<div class="block-work" style="top:${top}px;height:${h}px;" data-tooltip="Poste : ${s.start} - ${s.end}">
                            <span>${s.start}</span><i class="fas fa-chevron-down small opacity-50"></i><span>${s.end}</span>
                         </div>`;
            });

            if (m.pauses) m.pauses.forEach(p => {
                let ps = tToF(p.start),
                    pe = tToF(p.end),
                    top = (ps - START_H) * PX_H,
                    h = (pe - ps) * PX_H;
                tP += p.minutes;
                let cls = p.status === 'DEPASSEMENT' ? 'block-anomaly' : 'block-pause';
                pauses +=
                    `<div class="${cls}" style="top:${top}px;height:${h}px;" data-tooltip="${p.status || 'Pause'}: ${p.minutes}min">PAUSE</div>`;
            });

            // 1. ANOMALIE RETARD (>5 min)
            if (m.start_status === 'RETARD' && m.retard_minutes > 5) {
                let topPos = (tToF(m.segments[0].start) - START_H) * PX_H - 18;
                anomalies +=
                    `<div class="block-anomaly" style="top:${topPos}px;height:18px;" data-tooltip="Retard: ${m.retard_minutes}min">RETARD ${m.retard_minutes}m</div>`;
            }

            // 2. ANOMALIE DEPART ANTICIPÉ
            if (m.end_status === 'DEPART_ANTICIPE') {
                let lastSegEnd = tToF(m.segments[m.segments.length - 1].end);
                let topPos = (lastSegEnd - START_H) * PX_H;
                anomalies +=
                    `<div class="block-anomaly" style="top:${topPos}px;height:18px;background:#6366f1;border-color:#4f46e5;" data-tooltip="Sortie précoce à ${m.real_end}">DEPART ANTICIPÉ</div>`;
            }

            // 3. ANOMALIE OUBLI SORTIE
            if (m.is_oubli) {
                let lastSegEnd = tToF(m.segments[m.segments.length - 1].end);
                let topPos = (lastSegEnd - START_H) * PX_H;
                anomalies +=
                    `<div class="block-anomaly" style="top:${topPos}px;height:18px;background:#94a3b8;border-color:#64748b;" data-tooltip="Oubli de déconnexion">OUBLI SORTIE</div>`;
            }

            return `
                <div class="manager-col">
                    <div class="manager-head">
                        <div class="fw-bold text-primary truncate">${m.nom}</div>
                        <div style="font-size:0.65rem;" class="text-muted fw-bold">PROD: ${tW.toFixed(1)}h | PSE: ${Math.round(tP)}m</div>
                        <div class="badge mt-2 ${m.is_connected ? 'bg-success' : 'bg-light text-muted border'}">${m.is_connected ? '● ONLINE' : '○ OFFLINE'}</div>
                    </div>
                    <div class="position-relative" style="height:${17 * PX_H}px;">${segs}${pauses}${anomalies}</div>
                </div>`;
        }

        function renderTimeline() {
            let h = '';
            for (let i = START_H; i <= 22; i++) h += `<div class="hour-label">${i}:00</div>`;
            return h;
        }

        function updateNowIndicator() {
            const n = new Date(),
                h = n.getHours() + (n.getMinutes() / 60);
            if (h >= START_H && h <= 22) {
                $('.now-line-custom').css({
                    top: ((h - START_H) * PX_H + 110) + 'px',
                    display: 'block'
                });
            } else $('.now-line-custom').hide();
        }

        /* ================= GESTION DU TOOLTIP (POP) - FIX POSITION ================= */
        $(document).on('mouseenter', '[data-tooltip]', function() {
            const message = $(this).data('tooltip');
            const $tip = $('<div class="tooltip-box"></div>').text(message).appendTo('body');
            const rect = this.getBoundingClientRect();

            // Correction : ajout de window.pageXOffset / window.pageYOffset
            const left = rect.left + window.pageXOffset + (rect.width / 2) - ($tip.outerWidth() / 2);
            const top = rect.top + window.pageYOffset - $tip.outerHeight() - 5;

            $tip.css({
                left: left + 'px',
                top: top + 'px',
                opacity: 1
            });
        }).on('mouseleave', '[data-tooltip]', function() {
            $('.tooltip-box').remove();
        });

        $(document).ready(() => {
            loadData();
            setInterval(updateNowIndicator, 60000);
        });

        function updateProjetFilter() {
            const siteVal = $('#site_f').val();
            $('#projet_f option').each(function() {
                const site = $(this).data('site');
                if (siteVal && site != siteVal && $(this).val() !== "") $(this).hide();
                else $(this).show();
            });
            loadData();
        }

        $('#manager_search').on('input', function() {
            const term = $(this).val().toLowerCase();
            const filtered = allData.map(p => ({
                ...p,
                top_managers: p.top_managers.map(tm => ({
                    ...tm,
                    managers: tm.managers.filter(m => m.nom.toLowerCase().includes(term))
                })).filter(tm => tm.managers.length > 0)
            })).filter(p => p.top_managers.length > 0);
            renderMain(filtered);
        });
    </script>
@endpush
