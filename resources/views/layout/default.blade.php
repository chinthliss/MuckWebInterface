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

<header class="navbar">
    <!-- Row 1 - Logo and account related -->
    <div class="container-fluid flex-column flex-md-row">
        <a class="navbar-brand flex-grow-1" href="{{ url('/') }}">
            <img src="/public/sitelogo.png" alt="Site Logo" width="30" height="24" class="d-inline-block align-text-top">
            {{ config('app.name', 'MuckWebInterface') }}
        </a>
        <a class="navbar-nav nav-link px-2" href="#">Character name and Avatar</a>
        <a class="navbar-nav nav-link px-2" href="#">Notifications</a>
        <a class="navbar-nav nav-link px-2" href="#">Account</a>
        <a class="navbar-nav nav-link px-2" href="#">Logout</a>

    </div>
</header>

<main class="py-4">
    @yield('content')
</main>
</body>
</html>
