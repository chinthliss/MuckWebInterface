@extends('layout.page-with-navigation')

@section('title', 'Card Management')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => 'Card Management' ]
    ]) }}
@endsection

@section('content')
    TODO Implement Card Management Page
@endsection
