@extends('layouts.app')
  <!-- recherche css -->



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
                <h2>{{ $titre }}</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>{{ $titre }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow border-0">
            <div class="card-header contact-form">
                <form action="{{ route('import_agent') }}" method="POST" enctype="multipart/form-data" class="w-100 mt-2">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <label class="label-recherche"><i class="fa fa-file-excel"></i>  Importer des collaborateur (.xlsx, .csv)</label>
                            <div class="input-group">
                                <input class="form-control" type="file" name="agent_file" accept=".xlsx,.xls,.csv" required>
                                <button type="submit" class="btn btn-success">Téléverser</button>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <a href="{{ route('projet.create') }}" class="btn btn-primary shadow-sm">
                                <i class="fa fa-plus"></i> Créer un agent
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="agentTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Matricule</th>
                            <th>Nom & Prénom</th>
                            <th>Fonction</th>
                            <th>Email</th>
                            <th>Projet</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection

@push('scripts')
  {{-- On évite de multiplier les chargements de jQuery si déjà présent dans le layout --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />

  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script>
$(document).ready(function() {
    // Sécurité : détruire l'instance si elle existe déjà (utile avec TurboLinks ou Ajax)
    if ($.fn.DataTable.isDataTable('#agentTable')) {
        $('#agentTable').DataTable().destroy();
    }

    $('#agentTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('agents.ajax') }}",
        columns: [
            { data: 'site', name: 'projets.site_id' },
            { data: 'workday_id', name: 'agents.workday_id' },
            {
                data: null,
                name: 'agents.nom', // Changé en agents.nom pour que le tri fonctionne sur le nom
                render: function (data) {
                    const firstPrenom = data.prenom ? data.prenom.split(' ')[0] : '';
                    return `<strong>${data.nom}</strong> ${firstPrenom}`;
                },
                orderable: true,
                searchable: true
            },
            { data: 'fonction', name: 'agents.fonction' },
            { data: 'email', name: 'agents.email' },
            { data: 'projet', name: 'projets.designation' },
            { data: 'manager_nom', name: 'agents.manager' }
        ],

        dom: 'lBfrtip',
        pageLength: 50,
        lengthMenu: [
            [ 50, 100, 200, -1 ],
            [ '50', '100', '200', 'Tous' ]
        ],

        buttons: [
            { extend: 'copy', className: 'btn btn-secondary' },
            { extend: 'excel', className: 'btn btn-success' },
            { extend: 'pdf', className: 'btn btn-danger' },
            { extend: 'print', className: 'btn btn-info' }
        ],

        // TRADUCTION LOCALE (Supprime l'erreur CORS "Same Origin")
        language: {
            "sEmptyTable":     "Aucune donnée disponible dans le tableau",
            "sInfo":           "Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",
            "sInfoEmpty":      "Affichage de l'élément 0 à 0 sur 0 élément",
            "sInfoFiltered":   "(filtré à partir de _MAX_ éléments au total)",
            "sLengthMenu":     "Afficher _MENU_ éléments",
            "sLoadingRecords": "Chargement...",
            "sProcessing":     "Traitement en cours...",
            "sSearch":         "Rechercher :",
            "sZeroRecords":    "Aucun élément correspondant trouvé",
            "oPaginate": {
                "sFirst":    "Premier",
                "sLast":     "Dernier",
                "sNext":     "Suivant",
                "sPrevious": "Précédent"
            },
            "oAria": {
                "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
                "sSortDescending": ": activer pour trier la colonne par ordre décroissant"
            }
        }
    });
});
</script>
@endpush