@extends('layouts.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Centrer le titre et augmenter la taille */
        .column_title h2 {
            font-weight: 700;
            font-size: 2rem;
            text-align: center;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        /* Card */
        .card.shadow-sm {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Label style */
        .form-label {
            font-weight: 600;
            font-size: 1.1rem;
            color: #34495e;
        }

        /* Input and select fields */
        .form-control {
            border-radius: 8px;
            border: 1.5px solid #ced4da;
            transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .form-control:focus {
            border-color: #198754;
            /* Bootstrap green */
            box-shadow: 0 0 8px rgba(25, 135, 84, 0.25);
            outline: none;
        }

        /* Boutons */
        .btn-primary {
            background-color: #198754;
            border: none;
            font-weight: 600;
            padding: 10px 30px;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #157347;
        }

        .btn-danger {
            padding: 10px 30px;
            font-weight: 600;
            border-radius: 10px;
        }

        /* Espace entre les champs */
        .row.g-3>div {
            margin-bottom: 1.5rem;
        }

        /* Centrer la zone des boutons */
        .text-center.mt-3 {
            margin-top: 2rem !important;
        }

        /* Select2 : augmenter la hauteur pour mieux coller au style des inputs */
        .select2-container--default .select2-selection--single {
            height: 45px;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1.5px solid #ced4da;
            font-size: 1rem;
            color: #495057;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 31px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
            right: 10px;
        }
    </style>


    <div class="container-fluid">
        <div class="row column_title mb-3">
            <div class="col-md-12">
                <h2>Ajouter un collaborateur</h2>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" class="row g-3" action="{{ route('effectif.store') }}"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="col-md-4">
                                <label for="workday_id" class="form-label">Workday ID *</label>
                                <input id="workday_id" type="number" class="form-control" required name="workday_id"
                                    value="">
                            </div>

                            <div class="col-md-4">
                                <label for="nom" class="form-label">Nom *</label>
                                <input id="nom" type="text" class="form-control" required name="nom"
                                    value="">
                            </div>

                            <div class="col-md-4">
                                <label for="prenom" class="form-label">Prénom(s) *</label>
                                <input id="prenom" type="text" class="form-control" required name="prenom"
                                    value="">
                            </div>

                            <div class="col-md-4">
                                <label for="projet_id" class="form-label">Projet *</label>
                                <select name="projet_id" id="projet_id" class="form-control select2" required>
                                    <option value="" disabled selected>-- Sélectionner un projet --</option>
                                    @if (isset($projets))
                                        @foreach ($projets as $projet)
                                            <option value="{{ $projet->id }}">{{ $projet->designation }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="workday_id_manager" class="form-label">ID Workday Manager *</label>
                                <input id="workday_id_manager" type="number" class="form-control" required
                                    name="workday_id_manager" value="">
                            </div>

                            <div class="col-md-4">
                                <label for="fonction" class="form-label">Fonction *</label>
                                <select name="fonction" id="fonction" class="form-control select2" required>
                                    <option value="" disabled selected>-- Sélectionner une fonction --</option>
                                    @if (isset($fonctions))
                                        @foreach ($fonctions as $fonction)
                                            <option value="{{ $fonction }}">{{ $fonction }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="work_email" class="form-label">Email *</label>
                                <input id="work_email" type="email" class="form-control" required name="work_email"
                                    value="">
                            </div>

                            <div class="col-12 text-center mt-3">
                                <a href="{{ route('effectifs') }}" class="btn btn-danger"><i class="fa fa-close"></i>
                                    Annuler</a>
                                <button class="btn btn-primary"><i class="fa fa-save"></i> Ajouter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>




    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    width: '100%',
                    placeholder: 'Rechercher...',
                    allowClear: true
                });
            });
        </script>
    @endpush

@endsection
