@extends('layout.html-skeleton')

@section('page-navigation')
    <a class="nav-link" href="{{ route('multiplayer.home') }}">Dashboard</a>
    <a class="nav-link" href="{{ route('multiplayer.character.edit') }}">Edit Character</a>

    <a class="nav-link" href="{{ route('singleplayer.home') }}">Go to Singleplayer</a>
@endsection

@section('page-content')
    @yield('content')
@endsection
