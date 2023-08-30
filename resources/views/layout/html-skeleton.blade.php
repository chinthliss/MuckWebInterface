<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:type" content="website"/>
    <meta property="og:site_name" content="{{ config('app.name', 'MuckWebInterface') }}">
    <meta property="og:image" content="{{ url('/favicon32.png') }}">
    @hasSection('title')
        <meta property="og:title" content="@yield('title')">
    @endif
    <meta property="og:description" content="This is where a description of the site should go.">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Account if logged in -->
    @auth
        <meta name="account-id" content="{{ Auth::user()->id() }}">
    @endauth

    <!-- Character if set -->
    @Character
    <meta name="character-dbref" content="{{ Auth::user()->getCharacter()->dbref }}">
    <meta name="character-name" content="{{ Auth::user()->getCharacter()->name }}">
    @endCharacter

    <!-- Title -->
    @hasSection('title')
        <title>@yield('title') ({{ config('app.name', 'MuckWebInterface') }})</title>
    @else
        <title>{{ config('app.name', 'MuckWebInterface') }}</title>
    @endif

    <!-- FavIcons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/favicon32.png') }}">

    <!-- Default style / scripts loaded in by Vite -->
    @vite(['resources/sass/app.scss', 'resources/js/app.ts'])

</head>
<body>
<!-- Header bar -->
<header id="site-navigation-top" class="navbar navbar-dark site-navigation py-0">
    <div class="container-fluid flex-column flex-md-row align-items-md-stretch">

        <a class="navbar-brand flex-grow-1 d-inline-flex align-items-center p-2" href="{{ url('/') }}">
            <img src="{{ url('/sitelogo.png') }}" alt="Site Logo">
            <div>{{ config('app.name', 'MuckWebInterface') }}</div>
        </a>

        @guest
            <div class="d-flex align-items-center nav-link">
                <a class="navbar-nav d-inline-block text-decoration-none" href="{{ route('auth.login') }}">Login</a>
            </div>

        @else

            <div class="d-flex align-items-center nav-link">
                <a class="navbar-nav d-inline-block text-decoration-none" href="{{ route('notifications') }}">Notifications
                    <?php
                    /** @var App\User $user */
                    $user = auth()->user();
                    $count = resolve('App\AccountNotificationManager')->getUnreadNotificationsCountFor($user);
                    echo('<span class="badge text-black bg-info" id="account-notifications-unread-count">' . ($count ?: '') . '</span>');
                    ?>
                </a>
            </div>

            <div class="d-flex align-items-center nav-link">
                <a class="navbar-nav d-inline-block text-decoration-none" href="{{ route('account') }}">Account</a>
            </div>

            <div class="d-flex align-items-center nav-link">
                <a class="navbar-nav d-inline-block text-decoration-none" href="#"
                   onclick="event.preventDefault(); document.getElementById('site-logout-form').submit();">
                    Logout
                </a>
                <form id="site-logout-form" action="{{ route('auth.logout') }}" method="POST"
                      style="display: none;">
                    @csrf
                </form>
            </div>

            <div class="d-flex align-items-center nav-link">
                <a class="navbar-nav d-inline-block text-decoration-none" href="#site-character-select" role="button"
                   data-bs-toggle="offcanvas" aria-controls="site-character-select"
                >
                    @Character
                    {{ Auth::user()->getCharacter()->name }}
                    @else -Select Character- @endCharacter
                </a>
            </div>
        @endguest
    </div>
</header>
<!-- Content -->
<div id="site-below-header" class="container-fluid">

    @hasSection('page-navigation')
        <!-- Button to open Navigation if on mobile -->
        <button id="site_navigation_button" type="button" class="d-md-none btn btn-primary my-2 w-100">
            <i class="fas fa-bars btn-icon-left"></i>
            Navigation
        </button>

        <div class="row flex-xl-nowrap">

            <!-- Left side bar -->
            <nav id="site_navigation_left" class="col-12 col-md-3 col-xl-2">
                @yield('page-navigation')
            </nav>

            <!-- Body -->
            <div id="site_content" class="col-12 col-md-9 col-xl-10">
                @include('layout.html-content')
            </div>

        </div>
    @endif
    @sectionMissing('page-navigation')
        <div id="site_content">
            @include('layout.html-content')
        </div>
    @endif
</div>
</body>
</html>
