@extends('layout.html-skeleton')

@section('page-navigation')
    <div class="navbar-text">Singleplayer</div>
    <a class="nav-link" href="{{ route('singleplayer.home') }}">Home</a>
@endsection

@section('page-content')
    @yield('content')
@endsection
