@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $title }}</h1>

    <div class="card">
        <div class="card-header">Informations de l'utilisateur</div>
        <div class="card-body">
            <p><strong>Nom :</strong> {{ $user->name }}</p>
            <p><strong>Email :</strong> {{ $user->email }}</p>
            <p><strong>Rôles :</strong>
                @foreach ($user->roles as $role)
                    {{ $role->name }}<br>
                @endforeach
            </p>

            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning">Modifier</a>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Retour à la liste</a>
        </div>
    </div>
</div>
@endsection
