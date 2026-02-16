@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="column_title">
            <h2><i class="fas fa-lock-open me-2"></i> {{ $titre }}</h2>
        </div>

        <div class="card shadow-sm border-0 mt-3">
            <form action="{{ route('profil.permissions.grant', $role->id) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        @foreach ($permissions as $permission)
                            <div class="col-md-4 mb-3">
                                <div class="custom-control custom-checkbox p-3 border rounded">
                                    <input type="checkbox" name="role_{{ $permission->id }}" class="custom-control-input"
                                        id="perm_{{ $permission->id }}" {{ $permission->Checked }}>
                                    <label class="custom-control-label fw-bold" for="perm_{{ $permission->id }}">
                                        {{ $permission->name }}
                                    </label>
                                    <div class="small text-muted">Guard: {{ $permission->guard_name }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer bg-white text-center py-4">
                    <a href="{{ route('profil.permissions', $role->id) }}" class="btn btn-outline-danger px-4 me-2">
                        <i class="fa fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-success px-5 shadow-sm">
                        <i class="fa fa-save"></i> Enregistrer les droits
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
