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
    @vite(['resources/sass/app.scss', 'resources/ts/app.ts'])

</head>
<body>
@yield('page-body')
</body>
</html>
