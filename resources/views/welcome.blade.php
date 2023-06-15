@extends('layout.page-without-navigation')

@section('content')
    <div class="d-flex flex-column">
        <div class="d-flex justify-content-evenly">
            <div class="p-2">
            <h1>Welcome</h1>
            <p>This is a work-in-progress new site. At some point this whole welcome will be replaced.</p>
            </div>
        </div>
        <div class="d-flex justify-content-evenly">
            <div class="p-2">
                <h2>Singleplayer</h2>
                <p>Here is where we'll put more information about accessing singleplayer.</p>
                <a class="btn btn-primary w-100" href="{{ route('singleplayer.home') }}">
                    <i class="fas fa-user"></i>
                    Enter Singleplayer
                </a>
            </div>
            <div class="p-2">
                <h2>Multiplayer</h2>
                <p>Here is where we'll put more information about accessing multiplayer.</p>
                <a class="btn btn-primary w-100" href="{{ route('multiplayer.home') }}">
                    <i class="fas fa-users"></i>
                    Enter Multiplayer
                </a>
            </div>
        </div>
    </div>
@endsection


