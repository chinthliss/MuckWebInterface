@extends('layout.html-skeleton')

@section('page-navigation')
    <a class="nav-link" href="{{ route('account') }}">Account</a>

    <div><i class="fas fa-user btn-icon-left"></i>Single Player</div>
    <a class="nav-link" href="{{ route('singleplayer.home') }}">Introduction</a>

    <div><i class="fas fa-users btn-icon-left"></i>Multiplayer</div>
    <a class="nav-link" href="{{ route('multiplayer.home') }}">Dashboard</a>
    <a class="nav-link" href="{{ route('multiplayer.character') }}">Edit Character</a>
@endsection

@section('page-content')
    @yield('content')
@endsection
