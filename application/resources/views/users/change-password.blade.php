@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $title }}</h1>

    <form action="{{ route('auth.updatePassword') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="password">Nouveau mot de passe</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
    </form>
</div>
@endsection
