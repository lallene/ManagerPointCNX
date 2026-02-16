@extends('layouts.app')

@section('content')
    <style>
        /* Signature visuelle ManagerPoint */
        .column_title {
            background: var(--white);
            padding: 1.5rem;
            margin: -1rem -1rem 2rem -1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .column_title h2::before {
            content: "";
            width: 4px;
            height: 20px;
            background: var(--accent);
            display: inline-block;
            margin-right: 12px;
            border-radius: 2px;
            vertical-align: middle;
        }

        /* Style du Formulaire Audit */
        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
            outline: none;
        }

        .card {
            border: none;
            border-radius: 12px;
        }

        .page-subtitle {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--nav-bg);
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>

    <div class="container-fluid">
        {{-- En-tête de page --}}
        <div class="row column_title">
            <div class="col-md-12">
                <h2>{{ $titre }}</h2>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-md-10">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h4 class="page-subtitle text-uppercase">
                            <i class="fas fa-plus-circle me-2"></i>Nouveau Projet
                        </h4>

                        <form method="POST" class="row g-4" action="{{ route($link . '.store') }}"
                            enctype="multipart/form-data">
                            @csrf

                            {{-- SITE --}}
                            <div class="col-md-6">
                                <label for="site_id" class="form-label">
                                    <i class="fas fa-city me-2 text-muted"></i>Site <span class="text-danger ms-1">*</span>
                                </label>
                                <select name="site_id" class="form-select select2" id="site_id" required>
                                    <option value="" disabled selected>-- Sélectionner un site --</option>
                                    @foreach ($foreigns as $foreign)
                                        <option value="{{ $foreign->id }}">{{ $foreign->designation }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- DÉSIGNATION --}}
                            <div class="col-md-6">
                                <label for="designation" class="form-label">
                                    <i class="fas fa-tag me-2 text-muted"></i>Désignation <span
                                        class="text-danger ms-1">*</span>
                                </label>
                                <input id="designation" type="text" class="form-control" name="designation" required
                                    placeholder="Ex: Projet Relation Client">
                            </div>

                            {{-- DLT PROJET (EMAIL) --}}
                            <div class="col-md-12">
                                <label for="dltsuperviseur" class="form-label">
                                    <i class="fas fa-envelope-open-text me-2 text-muted"></i>Email DLT Projet <span
                                        class="text-danger ms-1">*</span>
                                </label>
                                <input id="dltsuperviseur" type="email" class="form-control" name="dltsuperviseur"
                                    required placeholder="manager@entreprise.com">
                            </div>

                            {{-- DESCRIPTION --}}
                            <div class="col-md-12">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-2 text-muted"></i>Description du projet
                                </label>
                                <textarea id="description" class="form-control" name="description" rows="4"
                                    placeholder="Précisez les objectifs ou détails du projet..."></textarea>
                            </div>

                            {{-- ACTIONS --}}
                            <div class="col-12 text-center mt-5 border-top pt-4">
                                <a href="{{ route('projet.index') }}" class="btn btn-outline-danger px-4 me-2">
                                    <i class="fas fa-times me-1"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-success px-5 shadow-sm">
                                    <i class="fas fa-check-circle me-1"></i> Créer le projet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialisation de Select2 si présent
            if ($('.select2').length > 0) {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }
        });
    </script>
@endpush
