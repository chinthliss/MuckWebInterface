@extends('layout.page-with-navigation-and-header')

@section('title', 'Admin')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Admin' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        TODO: Content for the admin page
    </div>

@endsection


