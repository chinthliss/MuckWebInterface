@extends('layout.page-without-navigation')

@section('content')
    <div class="d-flex flex-column">
        <div class="d-flex justify-content-evenly">
            <div class="p-2">
            <h1>Welcome to Flexible Survival</h1>
            <p>This is a work-in-progress new site. At some point this whole welcome will be replaced.</p>
            </div>
        </div>
        <div class="d-flex justify-content-evenly flex-column flex-xl-row">
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
    <hr class="mt-4 mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-evenly">
        <div class="p-2 text-center">
            <h2>Join the Discord!</h2>
            <iframe src="https://discord.com/widget?id=333559467218173953&theme=dark" width="350" height="500" allowtransparency="true" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
        </div>
        <div class="p-2 text-center">
            <h2>Other links:</h2>
            <a href="https://wiki.flexiblesurvival.com/w/">View the Wiki</a>
        </div>

    </div>
@endsection


