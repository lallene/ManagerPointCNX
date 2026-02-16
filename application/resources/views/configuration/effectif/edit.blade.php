@extends('layouts.app')

@section('content')
    <style>
        /* Signature visuelle ManagerPoint */
        .column_title {
            background: var(--white);
            padding: 1.5rem;
            margin: -1rem -1rem 2rem -1rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .column_title h2::before {
            content: "";
            width: 4px;
            height: 20px;
            background: var(--accent);
            display: inline-block;
            margin-right: 12px;
            border-radius: 2px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        /* --- STYLISATION SELECT2 MULTIPLE (Look Badge) --- */
        .select2-container--bootstrap-5 .select2-selection--multiple {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            min-height: 45px;
            padding: 5px 10px;
            background-color: #ffffff;
        }

        /* Style des "Tags/Badges" de projets */
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #eff6ff;
            /* Bleu très clair */
            border: 1px solid #bfdbfe;
            color: #1e40af;
            /* Bleu foncé */
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 4px;
        }

        /* Bouton de suppression du tag */
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: #dc2626;
            margin-right: 8px;
            border: none;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
            background-color: transparent;
            color: #991b1b;
        }

        /* Badge de compteur de projets */
        .badge-count {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>Modifier le collaborateur : <span class="text-primary">{{ $agent->nom }}
                            {{ $agent->prenom }}</span></h2>
                    <a href="{{ route('effectifs') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="{{ route('effectif.update', $agent->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">
                        {{-- Section Identité --}}
                        <div class="col-md-4">
                            <label class="form-label">Matricule (Workday ID)</label>
                            <input type="text" name="workday_id" class="form-control"
                                value="{{ old('workday_id', $agent->workday_id) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" value="{{ old('nom', $agent->nom) }}"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="prenom" class="form-control"
                                value="{{ old('prenom', $agent->prenom) }}" required>
                        </div>

                        {{-- Section Contact --}}
                        <div class="col-md-6">
                            <label class="form-label">Email Professionnel</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="email" name="work_email" class="form-control"
                                    value="{{ old('work_email', $agent->work_email) }}" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fonction / Titre</label>
                            <input type="text" name="fonction" class="form-control"
                                value="{{ old('fonction', $agent->fonction) }}" required>
                        </div>

                        {{-- Section Multi-Projets --}}
                        <div class="col-md-8">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                Projets Affectés
                                <span class="badge-count" id="project-counter">0 projet sélectionné</span>
                            </label>
                            <select name="projet_ids[]" class="form-select select2-multiple" multiple="multiple"
                                data-placeholder="Assigner un ou plusieurs projets..." required>
                                @foreach ($projetsList as $projet)
                                    <option value="{{ $projet->id }}"
                                        {{ (is_array(old('projet_ids')) && in_array($projet->id, old('projet_ids'))) || $agent->projets->contains($projet->id) ? 'selected' : '' }}>
                                        {{ $projet->designation }} (Site {{ $projet->site_id }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle text-info me-1"></i>
                                Cliquez pour ajouter. Les modifications seront effectives après enregistrement.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Responsable (Manager ID)</label>
                            <input type="text" name="manager" class="form-control"
                                value="{{ old('manager', $agent->manager) }}" placeholder="ID Workday du N+1">
                        </div>
                    </div>

                    <hr class="my-5 border-light">

                    <div class="row">
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">
                                <i class="fas fa-check-circle me-2"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Assets Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            const $select = $('.select2-multiple');

            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $select.data('placeholder'),
                allowClear: true,
                closeOnSelect: false, // Garde le menu ouvert pour sélection multiple rapide
            });

            // Mise à jour du compteur de projets
            function updateProjectCounter() {
                const count = $select.select2('data').length;
                const text = count + (count > 1 ? ' projets sélectionnés' : ' projet sélectionné');
                $('#project-counter').text(text);
            }

            $select.on('change', updateProjectCounter);
            updateProjectCounter(); // Appel au chargement de la page
        });
    </script>
@endpush
