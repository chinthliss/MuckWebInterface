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

    <!-- Title -->
    @hasSection('title')
        <title>@yield('title') ({{ config('app.name', 'MuckWebInterface') }})</title>
    @else
        <title>{{ config('app.name', 'MuckWebInterface') }}</title>
    @endif

    <!-- FavIcons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/favicon32.png') }}">

    <!-- Default style / scripts loaded in by Vite -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

</head>
<body>

<!-- Header bar -->
<header id="site-navigation-top" class="navbar navbar-dark site-navigation">
    <!-- Row 1 - Logo and account related -->
    <div class="container-fluid flex-column flex-md-row">
        <a class="navbar-brand flex-grow-1 d-inline-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ url('/sitelogo.png') }}" alt="Site Logo">
            <div>{{ config('app.name', 'MuckWebInterface') }}</div>
        </a>
        <a class="navbar-nav nav-link px-2" href="#">Character name and Avatar</a>
        <a class="navbar-nav nav-link px-2" href="#">Notifications</a>
        <a class="navbar-nav nav-link px-2" href="#">Account</a>
        <a class="navbar-nav nav-link px-2" href="#">Logout</a>

    </div>
</header>


@hasSection('page-navigation')
    <!-- Button to open Navigation if on mobile -->
    <div class="container-fluid">
        <button id="site_navigation_button" type="button" class="d-md-none btn btn-primary my-2">
            <i class="fas fa-bars btn-icon-left"></i>
            Navigation
        </button>
    </div>

    <div class="container-fluid">
        <div id="site-below-header" class="row flex-xl-nowrap">

            <!-- Left side bar -->
            <nav id="site_navigation_left" class="col-12 col-md-3 col-xl-2">
                @yield('page-navigation')
            </nav>

            <!-- Body -->
            <div id="site_content" class="col-12 col-md-9 col-xl-10">
                <!-- Javascript check -->
                <noscript>
                    <div class="p-3 mb-2 bg-danger text-light rounded">
                        This page requires javascript enabled in order to work.
                    </div>
                </noscript>

                <main class="py-4">
                    @yield('page-content')
                </main>

            </div>
        </div>
        @else
            <!-- No navigation set, using a single container for body -->
            <div class="container-fluid">
                <main id="site-below-header" class="py-4">
                    @yield('page-content')
                </main>
            </div>
@endif

</body>
</html>
