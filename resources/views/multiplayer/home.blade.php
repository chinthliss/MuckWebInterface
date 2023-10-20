@extends('layout.page-with-navigation-and-header')

@section('title', 'Multiplayer')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Multiplayer' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <p>TODO: Multiplayer home content</p>

        <p>The intent here is to show a list of your characters. Like the right sidebar of the existing site, but all
            characters at once.</p>

        <p>Also intending to allow them to be manually ordered AND take that ordering into the character select
            panel.</p>
    </div>

@endsection

@section('links')
    {{ PageLinks::render([
        ['title' => 'Reset Character Password', 'description' => "Use this page to change the password of a character if you've forgotten it.", 'url' => route('multiplayer.character.changepassword')],
    ]) }}
@endsection
