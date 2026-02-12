@extends('layouts.app')

@section('content')
<style>
    .contact-form {
        color: white !important;
        background-color: #174650;
        padding: 20px;
        border-radius: 8px 8px 0 0;
    }

    .styled-title {
        font-family: 'Segoe UI', sans-serif;
        font-size: 2.5rem;
        color: #0d6efd;
        font-weight: 700;
        margin-top: 90px;
        margin-bottom: 30px;
        text-align: center;
    }

    .label-recherche {
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        background: rgba(255, 255, 255, 0.1);
        padding: 4px 8px;
        border-radius: 4px;
        margin-bottom: 10px;
        display: inline-block;
    }
</style>

<div class="container-fluid">
     <div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2>Gestion des Projets</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Gestion des Projets</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow border-0">
        <div class="card-header contact-form">
            <form action="/importprojet" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="label-recherche"><i class="fa fa-file-excel"></i> Importer des projets (.xlsx, .csv)</label>
                        <div class="input-group">
                            <input class="form-control" type="file" name="projet_file" accept=".xlsx,.xls,.csv" required>
                            <button type="submit" class="btn btn-success">Téléverser</button>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <a href="{{ route('projet.create') }}" class="btn btn-primary shadow-sm">
                            <i class="fa fa-plus"></i> Nouveau Projet
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="siteTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>MSA ID</th>
                            <th>Designation</th>
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
    {{-- DataTables CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css"/>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
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
            ajax: "{{ route('projets.ajax') }}",
            columns: [
                { data: 'msa_id',         name: 'projets.msa_id' },
                { data: 'designation',    name: 'projets.designation' },
                { data: 'site_nom',       name: 'site_nom' }, // Correspond à l'alias filtré dans le contrôleur
                { data: 'dltsuperviseur', name: 'projets.dltsuperviseur' },
                { data: 'action',         name: 'action', orderable: false, searchable: false }
            ],
            dom: 'lBfrtip',
            buttons: [
                { extend: 'excel', className: 'btn btn-sm btn-outline-success', text: 'Exporter Excel' },
                { extend: 'pdf',   className: 'btn btn-sm btn-outline-danger',  text: 'Exporter PDF' }
            ],
            pageLength: 50,
            lengthMenu: [[10, 50, 100, -1], [10, 50, 100, "Tous"]],
            language: {
                "sProcessing":     "Traitement en cours...",
                "sSearch":         "Rechercher&nbsp;:",
                "sLengthMenu":     "Afficher _MENU_ projets",
                "sInfo":           "Affichage de _START_ &agrave; _END_ sur _TOTAL_ projets",
                "sInfoEmpty":      "Affichage de 0 &agrave; 0 sur 0 projet",
                "sEmptyTable":     "Aucune donnée disponible",
                "oPaginate": {
                    "sNext":     "Suivant",
                    "sPrevious": "Pr&eacute;c&eacute;dent"
                }
            }
        });
    });
    </script>
@endpush