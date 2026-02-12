@extends('layouts.app')

@section('content')
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
        border-color: #198754; /* Bootstrap green */
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
    .row.g-3 > div {
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
  .title-wrapper {
    display: flex;
    justify-content: center;  /* centre horizontalement */
    align-items: center;      /* centre verticalement */
    min-height: 100px;        /* ajustable selon besoin */
    margin-bottom: 1.5rem;
}

.page-subtitle {
    font-size: 24px;
    font-weight: 700;
    color: #007bff;
    position: relative;
}

.page-subtitle::after {
    content: "";
    display: block;
    margin: 8px auto 0;
    width: 60px;
    height: 3px;
    background-color: #007bff;
    border-radius: 2px;
}


</style>
    <div class="container-fluid">

        <div class="row column_title">
            <div class="col-md-12">
                <div class="page_title">
                    <h2>{{ $titre }}</h2>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
    <div class="col-md-10">
        <div class="card shadow rounded-4">
            <div class="card-body px-4 py-4">
              <div class="title-wrapper text-center">
    <h4 class="page-subtitle">Ajouter un projet</h4>
</div>



                <form method="post" class="row g-4" action="{{ route($link.'.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="col-md-6">
                        <label for="site_id" class="form-label fw-semibold">🏢 Site *</label>
                        <select name="site_id" class="form-select select2" id="site_id" required>
                            <option disabled selected>-- Sélectionner un site --</option>
                            @foreach($foreigns as $foreign)
                                <option value="{{ $foreign->id }}">{{ $foreign->designation }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="designation" class="form-label fw-semibold">📌 Désignation *</label>
                        <input id="designation" type="text" class="form-control" name="designation" required placeholder="Nom du projet">
                    </div>

                    <div class="col-md-6">
                        <label for="dltsuperviseur" class="form-label fw-semibold">👤 DLT Projet *</label>
                        <input id="dltsuperviseur" type="email" class="form-control" name="dltsuperviseur" required placeholder="ex: manager@email.com">
                    </div>

                    <div class="col-md-12">
                        <label for="description" class="form-label fw-semibold">📝 Description </label>
                        <textarea id="description" class="form-control" name="description" rows="4" placeholder="Détaillez ici..."></textarea>
                    </div>

                    <div class="col-12 text-center mt-3">
                        <a href="{{ route("projet.index") }}" class="btn btn-outline-danger px-4 me-2">
                            <i class="fa fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa fa-save"></i> Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    </div>
@stop
