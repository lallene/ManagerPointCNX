@extends('layouts.app')

@section('link')
    <style>
        :root {
            --bg-light: #f4f5f7;
            --card-bg: #ffffff;
            --accent-green: #2ECC71;
            --pause-gold: #F1C40F;
            --danger-red: #E74C3C;
            --hour-h: 60px;
            --text-dark: #1F1F1F;
            --text-muted: #555555;
        }

        body {
            background: var(--bg-light);
            color: var(--text-dark);
            font-family: 'Segoe UI', sans-serif;
        }

        /* KPI */
        .kpi-wrapper {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .kpi-card {
            background: var(--card-bg);
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 15px;
            min-width: 160px;
            flex: 1;
            text-align: center;
            cursor: pointer;
        }

        .kpi-card h6 {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0;
        }

        .kpi-card h3 {
            margin: 5px 0 0;
            font-weight: bold;
        }

        /* Timeline */
        .graph-frame {
            background: var(--card-bg);
            border: 1px solid #ccc;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .time-sidebar {
            width: 60px;
            background: #e9ecef;
            border-right: 1px solid #ccc;
            position: sticky;
            left: 0;
            z-index: 100;
        }

        .hour-label {
            height: var(--hour-h);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            border-bottom: 1px solid #ddd;
        }

        /* Managers */
        .manager-col {
            min-width: 260px;
            border-right: 1px solid #ccc;
            position: relative;
        }

        .manager-head {
            height: 110px;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid var(--accent-green);
            position: sticky;
            top: 0;
            z-index: 90;
        }

        /* Blocks */
        .block-work,
        .block-pause,
        .block-anomaly {
            position: absolute;
            width: 90%;
            left: 5%;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .block-work {
            background: var(--accent-green);
            color: #fff;
            flex-direction: column;
            justify-content: space-between;
            padding: 4px 0;
        }

        .block-pause {
            background: var(--pause-gold);
            color: #000;
        }

        .block-anomaly {
            background: var(--danger-red);
            color: #fff;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        /* Tooltip */
        .tooltip-box {
            position: absolute;
            background: #333;
            color: #fff;
            font-size: 0.75rem;
            padding: 6px 8px;
            border-radius: 6px;
            white-space: nowrap;
            z-index: 9999;
            pointer-events: none;
        }

        /* Now line */
        #now-line {
            position: absolute;
            left: 0;
            right: 0;
            border-top: 2px dashed #ff4500;
            z-index: 200;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid p-4">

        {{-- KPI --}}
        <div id="kpi-container" class="kpi-wrapper"></div>

        {{-- Filters --}}
        <div class="filter-bar row g-3 mb-4">
            <div class="col-md-2">
                <input type="text" id="manager_search" class="form-control form-control-sm"
                    placeholder="Recherche manager...">
            </div>
            <div class="col-md-2">
                <input type="date" id="target_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}"
                    onchange="loadData()">
            </div>
            <div class="col-md-3">
                <select id="site_f" class="form-select form-select-sm" onchange="updateProjetFilter()">
                    <option value="">Tous les sites</option>
                    @foreach ($sites as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <select id="projet_f" class="form-select form-select-sm" onchange="loadData()">
                    <option value="">Tous les projets</option>
                    @foreach ($projetsList as $p)
                        <option value="{{ $p->id }}" data-site="{{ $p->site }}">{{ $p->designation }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Graph --}}
        <div id="graph-zone" class="graph-frame"></div>

    </div>
@endsection

@push('scripts')
    <script>
        const PX_H = 60;
        const START_H = 6;
        let allData = []; // garder les données pour filtres

        /* ================= KPI ================= */
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
    <div class="kpi-card" data-tooltip="Nombre total de managers"><h6>Managers</h6><h3>${k.total}</h3></div>
    <div class="kpi-card" data-tooltip="Managers démarrés en retard"><h6>Retards</h6><h3>${k.retards}</h3></div>
    <div class="kpi-card" data-tooltip="Managers terminant plus tôt"><h6>Départs anticipés</h6><h3>${k.departAnticipe}</h3></div>
    <div class="kpi-card" data-tooltip="Pauses dépassant la durée"><h6>Dépassements pause</h6><h3>${k.depassement}</h3></div>
    <div class="kpi-card" data-tooltip="Somme des heures travaillées"><h6>Heures totales</h6><h3>${k.workHours.toFixed(1)}h</h3></div>
    `);
        }

        /* ================= LOAD ================= */
        function loadData() {
            $.get("{{ route('getDailyPlanningData') }}", {
                date: $('#target_date').val(),
                site_id: $('#site_f').val(),
                projet_id: $('#projet_f').val()
            }, data => {
                allData = data;
                renderMain(data);
            });
        }

        /* ================= FILTRE PROJET ================= */
        function updateProjetFilter() {
            const siteVal = $('#site_f').val();
            $('#projet_f option').each(function() {
                const site = $(this).data('site');
                if (siteVal && site !== siteVal) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
            $('#projet_f').val('');
            loadData();
        }

        /* ================= RECHERCHE MANAGER ================= */
        $('#manager_search').on('input', function() {
            const term = $(this).val().toLowerCase();
            if (!allData.length) return;
            const filteredData = allData.map(s => {
                const top_managers = s.top_managers.map(tm => {
                    const managers = tm.managers.filter(m => m.nom.toLowerCase().includes(term));
                    return {
                        ...tm,
                        managers
                    };
                }).filter(tm => tm.managers.length > 0);
                return {
                    ...s,
                    top_managers
                };
            }).filter(s => s.top_managers.length > 0);
            renderMain(filteredData);
        });

        /* ================= RENDER ================= */
        function renderMain(data) {
            let html = '';
            let flatManagers = [];
            data.forEach(s => s.top_managers.forEach(tm => tm.managers.forEach(m => flatManagers.push(m))));
            renderKPI(calculateKPI(flatManagers));

            data.forEach(s => {
                html +=
                    `<div class="p-2 bg-light text-dark fw-bold small border-bottom border-secondary">${s.site} - ${s.projet}</div>`;
                s.top_managers.forEach(tm => {
                    html += `<div class="d-flex overflow-auto">
                <div class="time-sidebar">${renderTimeline()}</div>
                <div class="d-flex position-relative flex-grow-1" style="height:${17*PX_H}px;">
                    <div id="now-line"></div>
                    ${tm.managers.map(renderManager).join('')}
                </div>
            </div>`;
                });
            });
            $('#graph-zone').html(html || '<div class="p-5 text-center">Aucune donnée</div>');
            updateNowIndicator();
        }

        /* ================= MANAGER ================= */
        function renderManager(m) {
            let tWork = 0,
                tPause = 0,
                segmentBlocks = '',
                pauseBlocks = '',
                anomalyBlocks = '';
            let firstStart = null,
                lastEnd = null;
            m.segments.forEach((s, i) => {
                let sF = tToF(s.start),
                    eF = tToF(s.end);
                if (i === 0) firstStart = sF;
                if (i === m.segments.length - 1) lastEnd = eF;
                let top = (sF - START_H) * PX_H;
                let height = (eF - sF) * PX_H;
                tWork += (eF - sF);
                segmentBlocks +=
                    `<div class="block-work" style="top:${top}px;height:${height}px;" data-tooltip="Travail ${s.start}-${s.end} (${(eF-sF).toFixed(2)}h)"><span>${s.start}</span><span>${s.end}</span></div>`;
            });
            if (m.pauses) m.pauses.forEach(p => {
                let psF = tToF(p.start),
                    peF = tToF(p.end);
                let top = (psF - START_H) * PX_H;
                let height = (peF - psF) * PX_H;
                tPause += p.minutes ?? ((peF - psF) * 60);
                let cls = p.status === 'DEPASSEMENT' ? 'block-anomaly' : 'block-pause';
                let label = p.status === 'DEPASSEMENT' ? `⚠ Dépassement (${p.minutes}m)` : 'PAUSE';
                pauseBlocks +=
                    `<div class="${cls}" style="top:${top}px;height:${height}px;" data-tooltip="${label}">${label}</div>`;
            });
            if (m.start_status === 'RETARD' && firstStart !== null) {
                let top = (firstStart - START_H) * PX_H - 20;
                anomalyBlocks +=
                    `<div class="block-anomaly" style="top:${top}px;height:18px;" data-tooltip="Retard ${m.retard_minutes} minutes">RETARD (${m.retard_minutes}m)</div>`;
            }
            if (m.end_status === 'DEPART_ANTICIPE' && lastEnd !== null) {
                let top = (lastEnd - START_H) * PX_H;
                anomalyBlocks +=
                    `<div class="block-anomaly" style="top:${top}px;height:18px;" data-tooltip="Départ anticipé ${m.depart_anticipe_minutes} minutes">DÉPART -${m.depart_anticipe_minutes}m</div>`;
            }

            return `<div class="manager-col">
        <div class="manager-head">
            <div class="fw-bold small">${m.nom}</div>
            <div style="font-size:0.65rem;color:#555;">TRV: ${tWork.toFixed(2)}h | PSE: ${Math.round(tPause)}m</div>
            <div class="badge mt-2 w-100 ${m.is_connected?'bg-success':'bg-danger'}">${m.is_connected?'CONNECTÉ':'HORS LIGNE'}</div>
        </div>
        <div class="position-relative" style="height:${17*PX_H}px;">${segmentBlocks}${pauseBlocks}${anomalyBlocks}</div>
    </div>`;
        }

        /* ================= TOOLTIP ================= */
        document.addEventListener('mouseover', e => {
            if (e.target.dataset.tooltip) {
                const t = document.createElement('div');
                t.className = 'tooltip-box';
                t.innerText = e.target.dataset.tooltip;
                document.body.appendChild(t);
                const r = e.target.getBoundingClientRect();
                t.style.left = r.left + 'px';
                t.style.top = (r.top - 35) + 'px';
            }
        });
        document.addEventListener('mouseout', e => {
            if (e.target.dataset.tooltip) document.querySelectorAll('.tooltip-box').forEach(el => el.remove());
        });

        /* ================= UTIL ================= */
        function renderTimeline() {
            let h = '';
            for (let i = START_H; i <= 22; i++) h += `<div class="hour-label">${i}:00</div>`;
            return h;
        }

        function updateNowIndicator() {
            const n = new Date();
            const h = n.getHours() + (n.getMinutes() / 60);
            if (h >= START_H && h <= 22) {
                const top = (h - START_H) * PX_H + 110;
                $('#now-line').css({
                    top: top + 'px',
                    display: 'block'
                });
            } else $('#now-line').hide();
        }

        function tToF(t) {
            if (!t) return 0;
            let p = t.split(':');
            return parseInt(p[0]) + (parseInt(p[1] || 0) / 60);
        }
        $(document).ready(() => {
            loadData();
            setInterval(updateNowIndicator, 30000);
        });
    </script>
@endpush
