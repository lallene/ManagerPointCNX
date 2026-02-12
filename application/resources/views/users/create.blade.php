@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $title }}</h1>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Nom</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="password_first_connection">Mot de passe (première connexion)</label>
            <input type="password" name="password_first_connection" id="password_first_connection" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="role_id">Rôle</label>
            <select name="role_id" id="role_id" class="form-control" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
    </form>
</div>
@endsection
