@extends('layout.page-with-navigation-and-header')

@section('title', 'Delete Account')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => 'Delete Account' ]
    ]) }}
@endsection

@section('content')
    This page is under construction.
@endsection
