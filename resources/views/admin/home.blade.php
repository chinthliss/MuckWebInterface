@extends('layout.page-with-navigation-and-header')

@section('title', 'Admin')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Admin' ]
    ]) }}
@endsection

@php($links = [
    "formEdit" => route('multiplayer.contribute.forms')
])

@section('content')
    <div class="container">
        <div class="row">
            <h1>Admin Dashboard</h1>
        </div>

        <admin-dashboard
            :links="{{ json_encode($links) }}"
        ></admin-dashboard>
    </div>


@endsection

@section('links')
    {{ PageLinks::render([
        ['title' => 'Avatar Doll Tester', 'description' => 'A visual list of every avatar doll in the system, along with information about its usage and the option to select one for more detailed testing.', 'url' => route('admin.avatar.dolllist')],
        ['title' => 'Avatar Item Tester', 'description' => 'A visual list of every avatar item in the system.', 'url' => route('admin.avatar.items')],
        ['title' => 'Avatar Gradients', 'description' => 'Show a detailed list of all gradients in the system.', 'url' => route('admin.avatar.gradients')],
        ['title' => 'Avatar Editor', 'description' => 'Shortcut to the regular avatar editor.', 'url' => route('multiplayer.avatar.edit')],
    ]) }}
@endsection
