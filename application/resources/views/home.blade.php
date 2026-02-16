@extends('layouts.app')

@section('content')
    <h1 class="mb-4">Dashboard RH</h1>

    {{-- Affiche ton composant Livewire --}}
    @livewire('dashboard')
@endsection
