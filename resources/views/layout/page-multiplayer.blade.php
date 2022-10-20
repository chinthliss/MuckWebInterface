@extends('layout.html-skeleton')

@section('page-navigation')
    <div class="navbar-text">Multiplayer</div>
    <a class="nav-link" href="{{ route('multiplayer.home') }}">Home</a>
@endsection

@section('page-content')
    @yield('content')
@endsection
