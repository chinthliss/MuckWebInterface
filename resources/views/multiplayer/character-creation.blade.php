@extends('layout.page-with-navigation')

@section('title', 'Create a New Character')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Create a New Character' ]
    ]) }}
@endsection

@section('content')
    <character-create
        :errors="{{ $errors }}"
        :old="{{ json_encode(old(), JSON_FORCE_OBJECT) }}"
    ></character-create>
@endsection


