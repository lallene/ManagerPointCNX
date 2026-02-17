@extends('layouts.app')

@section('content')
    {{-- Les styles restent identiques, ils sont très bien intégrés --}}
    <style>
        .column_title {
            background: var(--white);
            padding: 1.5rem;
            margin: -1rem -1rem 2rem -1rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .column_title h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--nav-bg);
            margin: 0;
            text-transform: uppercase;
            display: flex;
            align-items: center;
        }

        /* ... reste de tes styles ... */
    </style>

    <div class="container-fluid">
        <div class="column_title">
            <h2>Gestion des Projets</h2>
            <div class="breadcrumb-custom d-none d-md-block">
                <small class="text-muted">ManagerPoint / Gestion des Projets</small>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="siteTable" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>MSA ID</th>
                                <th>Désignation</th>
                                <th>Site</th>
                                <th>DLT Superviseur</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#siteTable')) {
                $('#siteTable').DataTable().destroy();
            }

            $('#siteTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                // On utilise la route singulière corrigée
                ajax: "{{ route('projet.ajax') }}",
                columns: [{
                        data: 'msa_id',
                        name: 'projets.msa_id'
                    },
                    {
                        data: 'projet_nom',
                        name: 'projets.designation'
                    },
                    {
                        data: 'site_nom',
                        name: 'sites.designation'
                    },
                    {
                        data: 'dltsuperviseur',
                        name: 'projets.dltsuperviseur'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                dom: '<"row mb-3"<"col-md-4"l><"col-md-4 text-center"B><"col-md-4"f>>rtip',
                buttons: [{
                        extend: 'excel',
                        className: 'btn btn-outline-success btn-sm mx-1',
                        text: '<i class="far fa-file-excel me-1"></i> Excel'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-outline-danger btn-sm mx-1',
                        text: '<i class="far fa-file-pdf me-1"></i> PDF'
                    }
                ],
                pageLength: 50,
                lengthMenu: [
                    [10, 50, 100, -1],
                    [10, 50, 100, "Tous"]
                ],
                language: {
                    // Utilisation du CDN pour éviter l'erreur 404 du fichier local
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json"
                },
                drawCallback: function() {
                    if (typeof bootstrap !== 'undefined') {
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
                        tooltipTriggerList.map(function(tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl)
                        });
                    }
                }
            });
        });
    </script>
@endpush
