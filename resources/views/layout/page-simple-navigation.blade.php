@extends('layout.html-skeleton')

@section('page-navigation')
    <a class="nav-link" href="{{ route('account') }}">Account</a>
    <a class="nav-link" href="{{ route('singleplayer.home') }}">Singleplayer</a>
    <a class="nav-link" href="{{ route('multiplayer.home') }}">Multiplayer</a>
@endsection

@section('page-content')
    @yield('content')
@endsection
